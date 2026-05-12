<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\Vote;
use App\Services\Audit\AuditLoggerService;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheInvalidator;

class VoteObserver
{
    private const AUDITABLE_FIELDS = [
        'tenant_id',
        'meeting_id',
        'title',
        'description',
        'type',
        'status',
        'quorum_required',
        'starts_at',
        'ends_at',
        'created_by',
        'deleted_at',
    ];

    /**
     * @var list<string>
     */
    private const KPI_FIELDS = [
        'status',
        'deleted_at',
        'tenant_id',
    ];

    public function __construct(
        private readonly AuditLoggerService $audit,
        private readonly ExecutiveDashboardCacheInvalidator $dashboardCacheInvalidator,
    ) {}

    public function created(Vote $vote): void
    {
        $this->audit->log(
            action: AuditAction::Created,
            auditable: $vote,
            oldValues: [],
            newValues: $vote->only(self::AUDITABLE_FIELDS),
        );

        $this->invalidateExecutiveDashboardCache($vote);
    }

    public function updated(Vote $vote): void
    {
        $dirty = array_intersect_key($vote->getChanges(), array_flip(self::AUDITABLE_FIELDS));
        if ($dirty === []) {
            return;
        }

        $action = array_key_exists('status', $dirty) ? AuditAction::StatusChanged : AuditAction::Updated;

        $old = [];
        foreach (array_keys($dirty) as $field) {
            $old[$field] = $vote->getOriginal($field);
        }

        $this->audit->log(
            action: $action,
            auditable: $vote,
            oldValues: $old,
            newValues: $vote->only(array_keys($dirty)),
        );

        if (array_intersect_key($dirty, array_flip(self::KPI_FIELDS)) !== []) {
            $this->invalidateExecutiveDashboardCache($vote);
        }
    }

    public function deleted(Vote $vote): void
    {
        $this->audit->log(
            action: AuditAction::Deleted,
            auditable: $vote,
            oldValues: $vote->only(self::AUDITABLE_FIELDS),
            newValues: [],
        );

        $this->invalidateExecutiveDashboardCache($vote);
    }

    public function restored(Vote $vote): void
    {
        $this->audit->log(
            action: AuditAction::Restored,
            auditable: $vote,
            oldValues: [],
            newValues: $vote->only(self::AUDITABLE_FIELDS),
        );

        $this->invalidateExecutiveDashboardCache($vote);
    }

    private function invalidateExecutiveDashboardCache(Vote $vote): void
    {
        if ($vote->tenant_id === null) {
            return;
        }

        $this->dashboardCacheInvalidator->invalidateForTenant((int) $vote->tenant_id);
    }
}
