<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\Minute;
use App\Services\Audit\AuditLoggerService;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheInvalidator;

class MinuteObserver
{
    /**
     * Não registrar `content` (conteúdo completo) na auditoria.
     */
    private const AUDITABLE_FIELDS = [
        'tenant_id',
        'meeting_id',
        'title',
        'status',
        'current_version_id',
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

    public function created(Minute $minute): void
    {
        $this->audit->log(
            action: AuditAction::Created,
            auditable: $minute,
            oldValues: [],
            newValues: $minute->only(self::AUDITABLE_FIELDS),
        );

        $this->invalidateExecutiveDashboardCache($minute);
    }

    public function updated(Minute $minute): void
    {
        $dirty = array_intersect_key($minute->getChanges(), array_flip(self::AUDITABLE_FIELDS));
        if ($dirty === []) {
            return;
        }

        $action = array_key_exists('status', $dirty) ? AuditAction::StatusChanged : AuditAction::Updated;

        $old = [];
        foreach (array_keys($dirty) as $field) {
            $old[$field] = $minute->getOriginal($field);
        }

        $this->audit->log(
            action: $action,
            auditable: $minute,
            oldValues: $old,
            newValues: $minute->only(array_keys($dirty)),
        );

        if (array_intersect_key($dirty, array_flip(self::KPI_FIELDS)) !== []) {
            $this->invalidateExecutiveDashboardCache($minute);
        }
    }

    public function deleted(Minute $minute): void
    {
        $this->audit->log(
            action: AuditAction::Deleted,
            auditable: $minute,
            oldValues: $minute->only(self::AUDITABLE_FIELDS),
            newValues: [],
        );

        $this->invalidateExecutiveDashboardCache($minute);
    }

    public function restored(Minute $minute): void
    {
        $this->audit->log(
            action: AuditAction::Restored,
            auditable: $minute,
            oldValues: [],
            newValues: $minute->only(self::AUDITABLE_FIELDS),
        );

        $this->invalidateExecutiveDashboardCache($minute);
    }

    private function invalidateExecutiveDashboardCache(Minute $minute): void
    {
        if ($minute->tenant_id === null) {
            return;
        }

        $this->dashboardCacheInvalidator->invalidateForTenant((int) $minute->tenant_id);
    }
}
