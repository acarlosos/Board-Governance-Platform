<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Services\Audit\AuditLoggerService;

final class MeetingObserver
{
    /**
     * @var list<string>
     */
    private const AUDITABLE_FIELDS = [
        'tenant_id',
        'board_id',
        'title',
        'description',
        'scheduled_at',
        'starts_at',
        'ends_at',
        'video_conference_url',
        'status',
        'created_by',
    ];

    public function created(\App\Models\Meeting $meeting): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Created,
            $meeting,
            oldValues: [],
            newValues: $this->onlyAllowed($meeting->getAttributes()),
            tenantId: (int) $meeting->tenant_id,
        );
    }

    public function updated(\App\Models\Meeting $meeting): void
    {
        $changes = $this->onlyAllowed($meeting->getChanges());
        if ($changes === []) {
            return;
        }

        $original = [];
        foreach (array_keys($changes) as $key) {
            $original[$key] = $meeting->getOriginal($key);
        }

        $action = array_key_exists('status', $changes)
            ? AuditAction::StatusChanged
            : AuditAction::Updated;

        app(AuditLoggerService::class)->log(
            $action,
            $meeting,
            oldValues: $original,
            newValues: $changes,
            tenantId: (int) $meeting->tenant_id,
        );
    }

    public function deleted(\App\Models\Meeting $meeting): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Deleted,
            $meeting,
            oldValues: $this->onlyAllowed($meeting->getOriginal()),
            newValues: [],
            tenantId: (int) $meeting->tenant_id,
        );
    }

    public function restored(\App\Models\Meeting $meeting): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Restored,
            $meeting,
            oldValues: [],
            newValues: $this->onlyAllowed($meeting->getAttributes()),
            tenantId: (int) $meeting->tenant_id,
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

