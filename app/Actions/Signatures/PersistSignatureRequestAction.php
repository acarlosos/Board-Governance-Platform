<?php

namespace App\Actions\Signatures;

use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Enums\SignatureProvider;
use App\Enums\SignatureRequestStatus;
use App\Models\Document;
use App\Models\Integration;
use App\Models\Minute;
use App\Models\SignatureRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PersistSignatureRequestAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): SignatureRequest
    {
        $data = $this->applyTenantGuard($actor, $data, null);

        $validated = $this->validate($data, existing: null);

        $request = new SignatureRequest;
        $request->fill(Arr::only($validated, [
            'tenant_id',
            'signable_type',
            'signable_id',
            'provider',
            'integration_id',
            'title',
            'message',
        ]));
        $request->status = SignatureRequestStatus::Draft;
        $request->requested_by = $actor->id;
        $request->save();

        app(RecordSignatureEventAction::class)->record(
            actor: $actor,
            request: $request,
            action: \App\Enums\SignatureEventAction::Created,
            status: $request->status->value,
            message: __('signatures.events.created'),
        );

        return $request->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, SignatureRequest $request, array $data): SignatureRequest
    {
        $data = $this->applyTenantGuard($actor, $data, $request);
        $validated = $this->validate($data, existing: $request);

        if ($request->status !== SignatureRequestStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => __('signatures.validation.only_draft_editable'),
            ]);
        }

        $request->fill(Arr::only($validated, [
            'tenant_id',
            'signable_type',
            'signable_id',
            'provider',
            'integration_id',
            'title',
            'message',
        ]));
        $request->save();

        app(RecordSignatureEventAction::class)->record(
            actor: $actor,
            request: $request,
            action: \App\Enums\SignatureEventAction::Created,
            status: $request->status->value,
            message: __('signatures.events.updated'),
        );

        return $request->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, ?SignatureRequest $existing): array
    {
        $validator = Validator::make($data, [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'signable_type' => ['required', 'string', Rule::in([Document::class, Minute::class])],
            'signable_id' => ['required', 'integer'],
            'provider' => ['required', 'string', Rule::in(array_map(fn (SignatureProvider $p) => $p->value, SignatureProvider::cases()))],
            'integration_id' => ['nullable', 'integer', 'exists:integrations,id'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $validator->after(function (\Illuminate\Validation\Validator $v) use ($existing): void {
            $safe = $v->safe();
            $tenantId = $safe->tenant_id ?? null;
            if (! $tenantId) {
                return;
            }

            $signableType = $safe->signable_type ?? null;
            $signableId = $safe->signable_id ?? null;
            $signable = $this->resolveSignable($signableType, $signableId);
            if (! $signable || (int) $signable->getAttribute('tenant_id') !== (int) $tenantId) {
                $v->errors()->add('signable_id', __('signatures.validation.signable_must_belong_to_tenant'));
            }

            $providerValue = $safe->provider ?? null;
            if ($providerValue) {
                $provider = SignatureProvider::from((string) $providerValue);
                $integrationId = $safe->integration_id ?? null;

                if ($provider === SignatureProvider::DocuSign) {
                    if (! $integrationId) {
                        $v->errors()->add('integration_id', __('signatures.validation.docusign_requires_integration'));
                    } else {
                        $integration = Integration::query()->withoutGlobalScopes()->find((int) $integrationId); // reason: lookup por id do payload; tenant restringido na action.
                        if (! $integration
                            || (int) $integration->tenant_id !== (int) $tenantId
                            || $integration->status !== IntegrationStatus::Active
                            || $integration->type !== IntegrationType::Signature
                            || $integration->provider !== IntegrationProvider::DocuSign
                        ) {
                            $v->errors()->add('integration_id', __('signatures.validation.integration_must_be_active_docusign'));
                        }
                    }
                } else {
                    // internal: não deve exigir integração
                    if ($integrationId) {
                        $integration = Integration::query()->withoutGlobalScopes()->find((int) $integrationId); // reason: lookup por id do payload; tenant restringido na action.
                        if (! $integration || (int) $integration->tenant_id !== (int) $tenantId) {
                            $v->errors()->add('integration_id', __('signatures.validation.integration_tenant_mismatch'));
                        }
                    }
                }
            }

            if ($existing && ! auth()->user()?->isSuperAdmin() && (int) $existing->tenant_id !== (int) $tenantId) {
                $v->errors()->add('tenant_id', __('signatures.validation.tenant_mismatch'));
            }
        });

        return $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyTenantGuard(User $actor, array $data, ?SignatureRequest $existing): array
    {
        if (! $actor->isSuperAdmin()) {
            $data['tenant_id'] = $actor->tenant_id;
        }

        if (! $actor->isSuperAdmin() && $actor->tenant_id === null) {
            throw ValidationException::withMessages([
                'tenant_id' => __('signatures.validation.tenant_required'),
            ]);
        }

        if ($existing && ! $actor->isSuperAdmin()) {
            if ((int) $existing->tenant_id !== (int) $actor->tenant_id) {
                throw ValidationException::withMessages([
                    'tenant_id' => __('signatures.validation.tenant_mismatch'),
                ]);
            }
        }

        return $data;
    }

    private function resolveSignable(?string $type, mixed $id): ?Model
    {
        if (! $type || ! $id) {
            return null;
        }

        $allowed = [Document::class, Minute::class];
        if (! in_array($type, $allowed, true)) {
            return null;
        }

        /** @var class-string<Model> $type */
        return $type::query()->withoutGlobalScopes()->find((int) $id); // reason: polimórfico; tenant do signature request já validado.
    }
}

