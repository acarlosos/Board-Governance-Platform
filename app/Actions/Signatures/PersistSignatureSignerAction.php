<?php

namespace App\Actions\Signatures;

use App\Enums\SignatureSignerStatus;
use App\Enums\SignatureRequestStatus;
use App\Models\SignatureRequest;
use App\Models\SignatureRequestSigner;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class PersistSignatureSignerAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, SignatureRequest $request, array $data): SignatureRequestSigner
    {
        $this->assertTenantAccess($actor, $request);

        if ($request->status !== SignatureRequestStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => __('signatures.validation.signers_only_in_draft'),
            ]);
        }

        $data['tenant_id'] = $request->tenant_id;
        $data['signature_request_id'] = $request->id;

        $validated = $this->validate($data);

        $signer = new SignatureRequestSigner;
        $signer->fill(Arr::only($validated, [
            'tenant_id',
            'signature_request_id',
            'user_id',
            'name',
            'email',
            'signing_order',
        ]));
        $signer->status = SignatureSignerStatus::Pending;
        $signer->save();

        return $signer->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, SignatureRequestSigner $signer, array $data): SignatureRequestSigner
    {
        $request = $signer->request;
        $this->assertTenantAccess($actor, $request);

        if ($request->status !== SignatureRequestStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => __('signatures.validation.signers_only_in_draft'),
            ]);
        }

        $data['tenant_id'] = $request->tenant_id;
        $data['signature_request_id'] = $request->id;

        $validated = $this->validate($data);

        $signer->fill(Arr::only($validated, [
            'user_id',
            'name',
            'email',
            'signing_order',
        ]));
        $signer->save();

        return $signer->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        $validator = Validator::make($data, [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'signature_request_id' => ['required', 'integer', 'exists:signature_requests,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'signing_order' => ['nullable', 'integer', 'min:1'],
        ]);

        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            $safe = $v->safe();
            $tenantId = $safe->tenant_id ?? null;
            $userId = $safe->user_id ?? null;
            if ($tenantId && $userId) {
                $user = User::query()->withoutGlobalScopes()->find((int) $userId);
                if (! $user || (int) $user->tenant_id !== (int) $tenantId) {
                    $v->errors()->add('user_id', __('signature-signers.validation.user_must_belong_to_tenant'));
                }
            }
        });

        return $validator->validate();
    }

    private function assertTenantAccess(User $actor, SignatureRequest $request): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->tenant_id === null || (int) $actor->tenant_id !== (int) $request->tenant_id) {
            throw ValidationException::withMessages([
                'tenant_id' => __('signatures.validation.tenant_mismatch'),
            ]);
        }
    }
}

