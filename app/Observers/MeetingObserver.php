<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\Meeting;
use App\Services\Audit\AuditLoggerService;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheInvalidator;

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

    /**
     * @var list<string>
     */
    private const KPI_FIELDS = [
        'status',
        'scheduled_at',
        'deleted_at',
        'tenant_id',
    ];

    public function __construct(
        private readonly AuditLoggerService $audit,
        private readonly ExecutiveDashboardCacheInvalidator $dashboardCacheInvalidator,
    ) {}

    public function created(Meeting $meeting): void
    {
        $this->audit->log(
            AuditAction::Created,
            $meeting,
            oldValues: [],
            newValues: $this->onlyAllowed($meeting->getAttributes()),
            tenantId: (int) $meeting->tenant_id,
        );

        $this->invalidateExecutiveDashboardCache($meeting);
    }

    public function updated(Meeting $meeting): void
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

        $this->audit->log(
            $action,
            $meeting,
            oldValues: $original,
            newValues: $changes,
            tenantId: (int) $meeting->tenant_id,
        );

        if (array_intersect_key($changes, array_flip(self::KPI_FIELDS)) !== []) {
            $this->invalidateExecutiveDashboardCache($meeting);
        }
    }

    public function deleted(Meeting $meeting): void
    {
        $this->audit->log(
            AuditAction::Deleted,
            $meeting,
            oldValues: $this->onlyAllowed($meeting->getOriginal()),
            newValues: [],
            tenantId: (int) $meeting->tenant_id,
        );

        $this->invalidateExecutiveDashboardCache($meeting);
    }

    public function restored(Meeting $meeting): void
    {
        $this->audit->log(
            AuditAction::Restored,
            $meeting,
            oldValues: [],
            newValues: $this->onlyAllowed($meeting->getAttributes()),
            tenantId: (int) $meeting->tenant_id,
        );

        $this->invalidateExecutiveDashboardCache($meeting);
    }

    private function invalidateExecutiveDashboardCache(Meeting $meeting): void
    {
        if ($meeting->tenant_id === null) {
            return;
        }

        $this->dashboardCacheInvalidator->invalidateForTenant((int) $meeting->tenant_id);
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
