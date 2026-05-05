<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Services\Audit\AuditLoggerService;

final class MeetingAgendaItemObserver
{
    /**
     * @var list<string>
     */
    private const AUDITABLE_FIELDS = [
        'tenant_id',
        'meeting_id',
        'title',
        'description',
        'order_column',
        'status',
    ];

    public function created(\App\Models\MeetingAgendaItem $item): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Created,
            $item,
            oldValues: [],
            newValues: $this->onlyAllowed($item->getAttributes()),
            tenantId: (int) $item->tenant_id,
        );
    }

    public function updated(\App\Models\MeetingAgendaItem $item): void
    {
        $changes = $this->onlyAllowed($item->getChanges());
        if ($changes === []) {
            return;
        }

        $original = [];
        foreach (array_keys($changes) as $key) {
            $original[$key] = $item->getOriginal($key);
        }

        $action = array_key_exists('status', $changes)
            ? AuditAction::StatusChanged
            : AuditAction::Updated;

        app(AuditLoggerService::class)->log(
            $action,
            $item,
            oldValues: $original,
            newValues: $changes,
            tenantId: (int) $item->tenant_id,
        );
    }

    public function deleted(\App\Models\MeetingAgendaItem $item): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Deleted,
            $item,
            oldValues: $this->onlyAllowed($item->getOriginal()),
            newValues: [],
            tenantId: (int) $item->tenant_id,
        );
    }

    public function restored(\App\Models\MeetingAgendaItem $item): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Restored,
            $item,
            oldValues: [],
            newValues: $this->onlyAllowed($item->getAttributes()),
            tenantId: (int) $item->tenant_id,
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

