<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\Vote;
use App\Services\Audit\AuditLoggerService;

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

    public function __construct(private readonly AuditLoggerService $audit)
    {
    }

    public function created(Vote $vote): void
    {
        $this->audit->log(
            action: AuditAction::Created,
            auditable: $vote,
            oldValues: [],
            newValues: $vote->only(self::AUDITABLE_FIELDS),
        );
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
    }

    public function deleted(Vote $vote): void
    {
        $this->audit->log(
            action: AuditAction::Deleted,
            auditable: $vote,
            oldValues: $vote->only(self::AUDITABLE_FIELDS),
            newValues: [],
        );
    }

    public function restored(Vote $vote): void
    {
        $this->audit->log(
            action: AuditAction::Restored,
            auditable: $vote,
            oldValues: [],
            newValues: $vote->only(self::AUDITABLE_FIELDS),
        );
    }
}

