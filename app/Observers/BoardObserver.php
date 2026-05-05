<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Services\Audit\AuditLoggerService;

final class BoardObserver
{
    /**
     * @var list<string>
     */
    private const AUDITABLE_FIELDS = [
        'tenant_id',
        'name',
        'description',
        'status',
        'created_by',
    ];

    public function created(\App\Models\Board $board): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Created,
            $board,
            oldValues: [],
            newValues: $this->onlyAllowed($board->getAttributes()),
            tenantId: (int) $board->tenant_id,
        );
    }

    public function updated(\App\Models\Board $board): void
    {
        $changes = $this->onlyAllowed($board->getChanges());
        if ($changes === []) {
            return;
        }

        $original = [];
        foreach (array_keys($changes) as $key) {
            $original[$key] = $board->getOriginal($key);
        }

        $action = array_key_exists('status', $changes)
            ? AuditAction::StatusChanged
            : AuditAction::Updated;

        app(AuditLoggerService::class)->log(
            $action,
            $board,
            oldValues: $original,
            newValues: $changes,
            tenantId: (int) $board->tenant_id,
        );
    }

    public function deleted(\App\Models\Board $board): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Deleted,
            $board,
            oldValues: $this->onlyAllowed($board->getOriginal()),
            newValues: [],
            tenantId: (int) $board->tenant_id,
        );
    }

    public function restored(\App\Models\Board $board): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Restored,
            $board,
            oldValues: [],
            newValues: $this->onlyAllowed($board->getAttributes()),
            tenantId: (int) $board->tenant_id,
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

