<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\Minute;
use App\Services\Audit\AuditLoggerService;

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

    public function __construct(private readonly AuditLoggerService $audit)
    {
    }

    public function created(Minute $minute): void
    {
        $this->audit->log(
            action: AuditAction::Created,
            auditable: $minute,
            oldValues: [],
            newValues: $minute->only(self::AUDITABLE_FIELDS),
        );
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
    }

    public function deleted(Minute $minute): void
    {
        $this->audit->log(
            action: AuditAction::Deleted,
            auditable: $minute,
            oldValues: $minute->only(self::AUDITABLE_FIELDS),
            newValues: [],
        );
    }

    public function restored(Minute $minute): void
    {
        $this->audit->log(
            action: AuditAction::Restored,
            auditable: $minute,
            oldValues: [],
            newValues: $minute->only(self::AUDITABLE_FIELDS),
        );
    }
}

