<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Services\Audit\AuditLoggerService;

final class BoardMemberObserver
{
    /**
     * @var list<string>
     */
    private const AUDITABLE_FIELDS = [
        'tenant_id',
        'board_id',
        'user_id',
        'role',
        'status',
        'joined_at',
        'left_at',
    ];

    public function created(\App\Models\BoardMember $member): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Created,
            $member,
            oldValues: [],
            newValues: $this->onlyAllowed($member->getAttributes()),
            tenantId: (int) $member->tenant_id,
        );
    }

    public function updated(\App\Models\BoardMember $member): void
    {
        $changes = $this->onlyAllowed($member->getChanges());
        if ($changes === []) {
            return;
        }

        $original = [];
        foreach (array_keys($changes) as $key) {
            $original[$key] = $member->getOriginal($key);
        }

        $action = array_key_exists('status', $changes)
            ? AuditAction::StatusChanged
            : AuditAction::Updated;

        app(AuditLoggerService::class)->log(
            $action,
            $member,
            oldValues: $original,
            newValues: $changes,
            tenantId: (int) $member->tenant_id,
        );
    }

    public function deleted(\App\Models\BoardMember $member): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Deleted,
            $member,
            oldValues: $this->onlyAllowed($member->getOriginal()),
            newValues: [],
            tenantId: (int) $member->tenant_id,
        );
    }

    public function restored(\App\Models\BoardMember $member): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Restored,
            $member,
            oldValues: [],
            newValues: $this->onlyAllowed($member->getAttributes()),
            tenantId: (int) $member->tenant_id,
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

