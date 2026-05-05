<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\Task;
use App\Services\Audit\AuditLoggerService;

class TaskObserver
{
    private const AUDITABLE_FIELDS = [
        'tenant_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'assigned_to',
        'created_by',
        'related_type',
        'related_id',
        'completed_at',
        'deleted_at',
    ];

    public function __construct(private readonly AuditLoggerService $audit)
    {
    }

    public function created(Task $task): void
    {
        $this->audit->log(
            action: AuditAction::Created,
            auditable: $task,
            oldValues: [],
            newValues: $task->only(self::AUDITABLE_FIELDS),
            tenantId: (int) $task->tenant_id,
        );
    }

    public function updated(Task $task): void
    {
        $dirty = array_intersect_key($task->getChanges(), array_flip(self::AUDITABLE_FIELDS));
        if ($dirty === []) {
            return;
        }

        $action = array_key_exists('status', $dirty) ? AuditAction::StatusChanged : AuditAction::Updated;

        $old = [];
        foreach (array_keys($dirty) as $field) {
            $old[$field] = $task->getOriginal($field);
        }

        $this->audit->log(
            action: $action,
            auditable: $task,
            oldValues: $old,
            newValues: $task->only(array_keys($dirty)),
            tenantId: (int) $task->tenant_id,
        );
    }

    public function deleted(Task $task): void
    {
        $this->audit->log(
            action: AuditAction::Deleted,
            auditable: $task,
            oldValues: $task->only(self::AUDITABLE_FIELDS),
            newValues: [],
            tenantId: (int) $task->tenant_id,
        );
    }

    public function restored(Task $task): void
    {
        $this->audit->log(
            action: AuditAction::Restored,
            auditable: $task,
            oldValues: [],
            newValues: $task->only(self::AUDITABLE_FIELDS),
            tenantId: (int) $task->tenant_id,
        );
    }
}

