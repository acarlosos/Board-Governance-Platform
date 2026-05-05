<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Integrations\IntegrationConfigSchemaRegistry;
use App\Models\Integration;
use App\Services\Audit\AuditLoggerService;

class IntegrationObserver
{
    private const AUDITABLE_FIELDS = [
        'tenant_id',
        'type',
        'provider',
        'name',
        'status',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
        'created_by',
        'deleted_at',
    ];

    public function __construct(private readonly AuditLoggerService $audit)
    {
    }

    public function created(Integration $integration): void
    {
        $this->audit->log(
            action: AuditAction::Created,
            auditable: $integration,
            oldValues: [],
            newValues: $this->auditPayload($integration, includeOriginal: false),
            tenantId: (int) $integration->tenant_id,
        );
    }

    public function updated(Integration $integration): void
    {
        $changes = $integration->getChanges();
        $dirty = array_intersect_key($changes, array_flip(self::AUDITABLE_FIELDS));

        $configChanged = array_key_exists('config', $changes);

        if ($dirty === [] && ! $configChanged) {
            return;
        }

        $action = array_key_exists('status', $dirty) ? AuditAction::StatusChanged : AuditAction::Updated;

        $old = [];
        foreach (array_keys($dirty) as $field) {
            $old[$field] = $integration->getOriginal($field);
        }

        $new = $integration->only(array_keys($dirty));

        if ($configChanged) {
            [$changedKeys, $safeChangedKeys] = $this->configChangedKeys($integration);
            $new['config_changed'] = true;
            $new['config_changed_keys'] = $safeChangedKeys;
            $old['config_changed'] = true;
            $old['config_changed_keys'] = $safeChangedKeys;
        }

        $this->audit->log(
            action: $action,
            auditable: $integration,
            oldValues: $old,
            newValues: $new,
            tenantId: (int) $integration->tenant_id,
        );
    }

    public function deleted(Integration $integration): void
    {
        $this->audit->log(
            action: AuditAction::Deleted,
            auditable: $integration,
            oldValues: $this->auditPayload($integration, includeOriginal: true),
            newValues: [],
            tenantId: (int) $integration->tenant_id,
        );
    }

    public function restored(Integration $integration): void
    {
        $this->audit->log(
            action: AuditAction::Restored,
            auditable: $integration,
            oldValues: [],
            newValues: $this->auditPayload($integration, includeOriginal: false),
            tenantId: (int) $integration->tenant_id,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function auditPayload(Integration $integration, bool $includeOriginal): array
    {
        $payload = $integration->only(self::AUDITABLE_FIELDS);

        // nunca auditar config; no máximo sinalizar mudança no updated()
        unset($payload['config']);

        return $payload;
    }

    /**
     * @return array{0: list<string>, 1: list<string>}
     */
    private function configChangedKeys(Integration $integration): array
    {
        $provider = $integration->provider;
        $secretKeys = IntegrationConfigSchemaRegistry::secretKeys($provider);

        $original = (array) ($integration->getOriginal('config') ?? []);
        $current = (array) ($integration->config ?? []);

        $keys = array_unique(array_merge(array_keys($original), array_keys($current)));
        sort($keys);

        $changed = [];
        foreach ($keys as $key) {
            $o = $original[$key] ?? null;
            $c = $current[$key] ?? null;
            if ($o !== $c) {
                $changed[] = (string) $key;
            }
        }

        $safe = array_values(array_filter($changed, fn (string $k): bool => ! in_array($k, $secretKeys, true)));

        return [$changed, $safe];
    }
}

