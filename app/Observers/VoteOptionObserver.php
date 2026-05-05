<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\VoteOption;
use App\Services\Audit\AuditLoggerService;

class VoteOptionObserver
{
    private const AUDITABLE_FIELDS = [
        'tenant_id',
        'vote_id',
        'title',
        'description',
        'order_column',
        'deleted_at',
    ];

    public function __construct(private readonly AuditLoggerService $audit)
    {
    }

    public function created(VoteOption $option): void
    {
        $this->audit->log(
            action: AuditAction::Updated,
            auditable: $option->vote,
            oldValues: [],
            newValues: [
                'event' => 'option_created',
                'vote_option_id' => $option->id,
                'title' => $option->title,
                'order_column' => $option->order_column,
            ],
        );
    }

    public function updated(VoteOption $option): void
    {
        $dirty = array_intersect_key($option->getChanges(), array_flip(self::AUDITABLE_FIELDS));
        if ($dirty === []) {
            return;
        }

        $this->audit->log(
            action: AuditAction::Updated,
            auditable: $option->vote,
            oldValues: [
                'event' => 'option_updated',
                'vote_option_id' => $option->id,
            ],
            newValues: [
                'event' => 'option_updated',
                'vote_option_id' => $option->id,
                'changes' => $dirty,
            ],
        );
    }
}

