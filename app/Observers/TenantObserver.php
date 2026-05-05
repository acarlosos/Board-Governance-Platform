<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Services\Audit\AuditLoggerService;

final class TenantObserver
{
    /**
     * @var list<string>
     */
    private const AUDITABLE_FIELDS = [
        'name',
        'slug',
        'document',
        'status',
    ];

    public function created(\App\Models\Tenant $tenant): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Created,
            $tenant,
            oldValues: [],
            newValues: $this->onlyAllowed($tenant->getAttributes()),
            tenantId: (int) $tenant->getKey(),
        );
    }

    public function updated(\App\Models\Tenant $tenant): void
    {
        $changes = $this->onlyAllowed($tenant->getChanges());
        if ($changes === []) {
            return;
        }

        $original = [];
        foreach (array_keys($changes) as $key) {
            $original[$key] = $tenant->getOriginal($key);
        }

        $action = array_key_exists('status', $changes)
            ? AuditAction::StatusChanged
            : AuditAction::Updated;

        app(AuditLoggerService::class)->log(
            $action,
            $tenant,
            oldValues: $original,
            newValues: $changes,
            tenantId: (int) $tenant->getKey(),
        );
    }

    public function deleted(\App\Models\Tenant $tenant): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Deleted,
            $tenant,
            oldValues: $this->onlyAllowed($tenant->getOriginal()),
            newValues: [],
            tenantId: (int) $tenant->getKey(),
        );
    }

    public function restored(\App\Models\Tenant $tenant): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Restored,
            $tenant,
            oldValues: [],
            newValues: $this->onlyAllowed($tenant->getAttributes()),
            tenantId: (int) $tenant->getKey(),
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function onlyAllowed(array $values): array
    {
        return array_intersect_key($values, array_flip(self::AUDITABLE_FIELDS));
    }
}

