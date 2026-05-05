<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\NotificationCenter;
use App\Services\Audit\AuditLoggerService;

class NotificationCenterObserver
{
    private const AUDITABLE_FIELDS = [
        'tenant_id',
        'user_id',
        'channel',
        'status',
        'related_type',
        'related_id',
        'read_at',
        'sent_at',
        'deleted_at',
    ];

    public function __construct(private readonly AuditLoggerService $audit)
    {
    }

    public function created(NotificationCenter $notification): void
    {
        $this->audit->log(
            action: AuditAction::Created,
            auditable: $notification,
            oldValues: [],
            newValues: $notification->only(self::AUDITABLE_FIELDS),
            tenantId: (int) $notification->tenant_id,
        );
    }

    public function updated(NotificationCenter $notification): void
    {
        $dirty = array_intersect_key($notification->getChanges(), array_flip(self::AUDITABLE_FIELDS));

        // nunca auditar title/body/metadata
        if ($dirty === []) {
            return;
        }

        $old = [];
        foreach (array_keys($dirty) as $field) {
            $old[$field] = $notification->getOriginal($field);
        }

        $action = array_key_exists('status', $dirty) ? AuditAction::StatusChanged : AuditAction::Updated;

        $this->audit->log(
            action: $action,
            auditable: $notification,
            oldValues: $old,
            newValues: $notification->only(array_keys($dirty)),
            tenantId: (int) $notification->tenant_id,
        );
    }
}

