<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\SignatureRequest;
use App\Services\Audit\AuditLoggerService;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheInvalidator;

class SignatureRequestObserver
{
    private const AUDITABLE_FIELDS = [
        'tenant_id',
        'signable_type',
        'signable_id',
        'provider',
        'integration_id',
        'title',
        'status',
        'requested_by',
        'requested_at',
        'completed_at',
        'cancelled_at',
        'external_id',
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

    public function created(SignatureRequest $request): void
    {
        $this->audit->log(
            action: AuditAction::Created,
            auditable: $request,
            oldValues: [],
            newValues: $request->only(self::AUDITABLE_FIELDS),
            tenantId: (int) $request->tenant_id,
        );

        $this->invalidateExecutiveDashboardCache($request);
    }

    public function updated(SignatureRequest $request): void
    {
        $dirty = array_intersect_key($request->getChanges(), array_flip(self::AUDITABLE_FIELDS));
        if ($dirty === []) {
            return;
        }

        $action = array_key_exists('status', $dirty) ? AuditAction::StatusChanged : AuditAction::Updated;

        $old = [];
        foreach (array_keys($dirty) as $field) {
            $old[$field] = $request->getOriginal($field);
        }

        $this->audit->log(
            action: $action,
            auditable: $request,
            oldValues: $old,
            newValues: $request->only(array_keys($dirty)),
            tenantId: (int) $request->tenant_id,
        );

        if (array_intersect_key($dirty, array_flip(self::KPI_FIELDS)) !== []) {
            $this->invalidateExecutiveDashboardCache($request);
        }
    }

    public function deleted(SignatureRequest $request): void
    {
        $this->audit->log(
            action: AuditAction::Deleted,
            auditable: $request,
            oldValues: $request->only(self::AUDITABLE_FIELDS),
            newValues: [],
            tenantId: (int) $request->tenant_id,
        );

        $this->invalidateExecutiveDashboardCache($request);
    }

    public function restored(SignatureRequest $request): void
    {
        $this->audit->log(
            action: AuditAction::Restored,
            auditable: $request,
            oldValues: [],
            newValues: $request->only(self::AUDITABLE_FIELDS),
            tenantId: (int) $request->tenant_id,
        );

        $this->invalidateExecutiveDashboardCache($request);
    }

    private function invalidateExecutiveDashboardCache(SignatureRequest $request): void
    {
        if ($request->tenant_id === null) {
            return;
        }

        $this->dashboardCacheInvalidator->invalidateForTenant((int) $request->tenant_id);
    }
}
