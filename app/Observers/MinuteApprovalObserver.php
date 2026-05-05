<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Enums\MinuteApprovalStatus;
use App\Models\MinuteApproval;
use App\Services\Audit\AuditLoggerService;

class MinuteApprovalObserver
{
    public function __construct(private readonly AuditLoggerService $audit)
    {
    }

    public function created(MinuteApproval $approval): void
    {
        $status = $approval->status instanceof MinuteApprovalStatus ? $approval->status->value : (string) $approval->status;

        $this->audit->log(
            action: AuditAction::Updated,
            auditable: $approval->minute,
            oldValues: [],
            newValues: [
                'event' => 'approval_created',
                'minute_approval_id' => $approval->id,
                'user_id' => $approval->user_id,
                'status' => $status,
            ],
        );
    }

    public function updated(MinuteApproval $approval): void
    {
        $dirty = $approval->getChanges();
        if ($dirty === []) {
            return;
        }

        $oldStatus = $approval->getOriginal('status');
        $oldStatusValue = $oldStatus instanceof MinuteApprovalStatus ? $oldStatus->value : (string) $oldStatus;

        $newStatus = $approval->status;
        $newStatusValue = $newStatus instanceof MinuteApprovalStatus ? $newStatus->value : (string) $newStatus;

        $this->audit->log(
            action: AuditAction::Updated,
            auditable: $approval->minute,
            oldValues: [
                'event' => 'approval_updated',
                'minute_approval_id' => $approval->id,
                'status' => $oldStatusValue,
                'approved_at' => $approval->getOriginal('approved_at'),
                'rejected_at' => $approval->getOriginal('rejected_at'),
            ],
            newValues: [
                'event' => 'approval_updated',
                'minute_approval_id' => $approval->id,
                'status' => $newStatusValue,
                'approved_at' => $approval->approved_at,
                'rejected_at' => $approval->rejected_at,
            ],
        );
    }
}

