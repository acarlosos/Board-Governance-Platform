<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\NotificationCenter;
use App\Services\Audit\AuditLoggerService;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheInvalidator;

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

    /**
     * @var list<string>
     */
    private const KPI_FIELDS = [
        'status',
        'read_at',
        'deleted_at',
        'tenant_id',
    ];

    public function __construct(
        private readonly AuditLoggerService $audit,
        private readonly ExecutiveDashboardCacheInvalidator $dashboardCacheInvalidator,
    ) {}

    public function created(NotificationCenter $notification): void
    {
        $this->audit->log(
            action: AuditAction::Created,
            auditable: $notification,
            oldValues: [],
            newValues: $notification->only(self::AUDITABLE_FIELDS),
            tenantId: (int) $notification->tenant_id,
        );

        $this->invalidateExecutiveDashboardCache($notification);
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

        if (array_intersect_key($dirty, array_flip(self::KPI_FIELDS)) !== []) {
            $this->invalidateExecutiveDashboardCache($notification);
        }
    }

    public function deleted(NotificationCenter $notification): void
    {
        $this->audit->log(
            action: AuditAction::Deleted,
            auditable: $notification,
            oldValues: $notification->only(self::AUDITABLE_FIELDS),
            newValues: [],
            tenantId: (int) $notification->tenant_id,
        );

        $this->invalidateExecutiveDashboardCache($notification);
    }

    public function restored(NotificationCenter $notification): void
    {
        $this->audit->log(
            action: AuditAction::Restored,
            auditable: $notification,
            oldValues: [],
            newValues: $notification->only(self::AUDITABLE_FIELDS),
            tenantId: (int) $notification->tenant_id,
        );

        $this->invalidateExecutiveDashboardCache($notification);
    }

    private function invalidateExecutiveDashboardCache(NotificationCenter $notification): void
    {
        if ($notification->tenant_id === null) {
            return;
        }

        $this->dashboardCacheInvalidator->invalidateForTenant((int) $notification->tenant_id);
    }
}
