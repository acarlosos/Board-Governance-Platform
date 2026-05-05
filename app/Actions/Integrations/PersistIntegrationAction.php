<?php

namespace App\Actions\Integrations;

use App\Enums\IntegrationLogAction;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Integrations\IntegrationConfigSchemaRegistry;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PersistIntegrationAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Integration
    {
        $data = $this->applyTenantGuard($actor, $data, null);

        $validated = $this->validate($data, existingConfig: []);

        $integration = new Integration;
        $integration->fill(Arr::only($validated, ['tenant_id', 'type', 'provider', 'name']));
        $integration->status = IntegrationStatus::Inactive;
        $integration->created_by = $actor->id;

        $integration->config = $this->sanitizeConfigForSave(
            provider: IntegrationProvider::from((string) $validated['provider']),
            incoming: (array) ($validated['config'] ?? []),
            existing: [],
        );

        $integration->save();

        app(RecordIntegrationLogAction::class)->record(
            actor: $actor,
            integration: $integration,
            action: IntegrationLogAction::Created,
            status: 'success',
            message: __('integrations.logs.created'),
            context: [],
        );

        return $integration->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Integration $integration, array $data): Integration
    {
        $data = $this->applyTenantGuard($actor, $data, $integration);

        $existingConfig = (array) ($integration->config ?? []);
        $validated = $this->validate($data, existingConfig: $existingConfig);

        $provider = IntegrationProvider::from((string) $validated['provider']);

        $integration->fill(Arr::only($validated, ['tenant_id', 'type', 'provider', 'name']));

        $incomingConfig = (array) ($validated['config'] ?? []);

        $integration->config = $this->sanitizeConfigForSave(
            provider: $provider,
            incoming: $incomingConfig,
            existing: $existingConfig,
        );

        $integration->save();

        app(RecordIntegrationLogAction::class)->record(
            actor: $actor,
            integration: $integration,
            action: IntegrationLogAction::Updated,
            status: 'success',
            message: __('integrations.logs.updated'),
            context: [],
        );

        return $integration->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, array $existingConfig = []): array
    {
        $validator = Validator::make($data, [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'type' => ['required', 'string', Rule::in(array_map(fn (IntegrationType $t) => $t->value, IntegrationType::cases()))],
            'provider' => ['required', 'string', Rule::in(array_map(fn (IntegrationProvider $p) => $p->value, IntegrationProvider::cases()))],
            'name' => ['required', 'string', 'max:255'],
            'config' => ['nullable', 'array'],
        ]);

        $validator->after(function (\Illuminate\Validation\Validator $v) use ($existingConfig): void {
            $safe = $v->safe();

            $providerValue = $safe->provider ?? null;
            if (! $providerValue) {
                return;
            }

            try {
                $provider = IntegrationProvider::from((string) $providerValue);
            } catch (\ValueError) {
                return;
            }

            $config = (array) ($safe->config ?? []);
            $secretKeys = IntegrationConfigSchemaRegistry::secretKeys($provider);

            // valida obrigatórios (sem chamar API externa)
            $missing = [];
            foreach (IntegrationConfigSchemaRegistry::requiredKeys($provider) as $key) {
                $value = $config[$key] ?? null;
                if (($value === null || $value === '') && in_array($key, $secretKeys, true)) {
                    // edição: secret obrigatório pode vir vazio e manter o existente
                    $existing = $existingConfig[$key] ?? null;
                    if ($existing !== null && $existing !== '') {
                        continue;
                    }
                }

                if ($value === null || $value === '') {
                    $missing[] = $key;
                }
            }

            if ($missing !== []) {
                $v->errors()->add('config', __('integrations.validation.missing_required').': '.implode(', ', $missing));
            }
        });

        return $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyTenantGuard(User $actor, array $data, ?Integration $existing): array
    {
        if (! $actor->isSuperAdmin()) {
            $data['tenant_id'] = $actor->tenant_id;
        }

        if (! $actor->isSuperAdmin() && $actor->tenant_id === null) {
            throw ValidationException::withMessages([
                'tenant_id' => __('integrations.validation.tenant_required'),
            ]);
        }

        if ($existing && ! $actor->isSuperAdmin()) {
            if ((int) $existing->tenant_id !== (int) $actor->tenant_id) {
                throw ValidationException::withMessages([
                    'tenant_id' => __('integrations.validation.tenant_mismatch'),
                ]);
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    private function sanitizeConfigForSave(IntegrationProvider $provider, array $incoming, array $existing): array
    {
        $schema = IntegrationConfigSchemaRegistry::schemaFor($provider);
        $secretKeys = IntegrationConfigSchemaRegistry::secretKeys($provider);

        // manter só chaves conhecidas
        $incoming = Arr::only($incoming, array_keys($schema));

        // regra: secret vazio na edição mantém valor existente
        foreach ($secretKeys as $key) {
            if (array_key_exists($key, $incoming) && ($incoming[$key] === null || $incoming[$key] === '')) {
                if (array_key_exists($key, $existing)) {
                    $incoming[$key] = $existing[$key];
                } else {
                    unset($incoming[$key]);
                }
            }
        }

        // garantir que não salva secrets em branco
        foreach ($secretKeys as $key) {
            if (array_key_exists($key, $incoming) && ($incoming[$key] === null || $incoming[$key] === '')) {
                unset($incoming[$key]);
            }
        }

        return $incoming;
    }
}

