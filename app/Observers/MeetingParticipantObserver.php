<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Services\Audit\AuditLoggerService;

final class MeetingParticipantObserver
{
    /**
     * @var list<string>
     */
    private const AUDITABLE_FIELDS = [
        'tenant_id',
        'meeting_id',
        'user_id',
        'role',
        'status',
        'responded_at',
    ];

    public function created(\App\Models\MeetingParticipant $participant): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Created,
            $participant,
            oldValues: [],
            newValues: $this->onlyAllowed($participant->getAttributes()),
            tenantId: (int) $participant->tenant_id,
        );
    }

    public function updated(\App\Models\MeetingParticipant $participant): void
    {
        $changes = $this->onlyAllowed($participant->getChanges());
        if ($changes === []) {
            return;
        }

        $original = [];
        foreach (array_keys($changes) as $key) {
            $original[$key] = $participant->getOriginal($key);
        }

        $action = array_key_exists('status', $changes)
            ? AuditAction::StatusChanged
            : AuditAction::Updated;

        app(AuditLoggerService::class)->log(
            $action,
            $participant,
            oldValues: $original,
            newValues: $changes,
            tenantId: (int) $participant->tenant_id,
        );
    }

    public function deleted(\App\Models\MeetingParticipant $participant): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Deleted,
            $participant,
            oldValues: $this->onlyAllowed($participant->getOriginal()),
            newValues: [],
            tenantId: (int) $participant->tenant_id,
        );
    }

    public function restored(\App\Models\MeetingParticipant $participant): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Restored,
            $participant,
            oldValues: [],
            newValues: $this->onlyAllowed($participant->getAttributes()),
            tenantId: (int) $participant->tenant_id,
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

