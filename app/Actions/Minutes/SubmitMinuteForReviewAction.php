<?php

namespace App\Actions\Minutes;

use App\Enums\MinuteApprovalStatus;
use App\Enums\MinuteStatus;
use App\Models\Minute;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SubmitMinuteForReviewAction
{
    public function submit(User $actor, Minute $minute): Minute
    {
        $this->assertTenantAccess($actor, $minute);

        if ($minute->status === MinuteStatus::InReview) {
            // Idempotência: já está em revisão, só garante approvals.
            return $this->syncApprovals($minute);
        }

        if ($minute->status !== MinuteStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => __('minutes.validation.submit_only_in_draft'),
            ]);
        }

        if (! $minute->status->canTransitionTo(MinuteStatus::InReview)) {
            throw ValidationException::withMessages([
                'status' => __('minutes.validation.invalid_status_transition'),
            ]);
        }

        return DB::transaction(function () use ($minute): Minute {
            $minute = $this->syncApprovals($minute);

            $minute->status = MinuteStatus::InReview;
            $minute->save();

            return $minute->fresh();
        });
    }

    private function syncApprovals(Minute $minute): Minute
    {
        $participantIds = $minute->meeting->participants()
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();

        if ($participantIds === []) {
            throw ValidationException::withMessages([
                'meeting_id' => __('minutes.validation.no_participants_for_review'),
            ]);
        }

        foreach ($participantIds as $userId) {
            $minute->approvals()->updateOrCreate(
                ['tenant_id' => $minute->tenant_id, 'user_id' => $userId],
                [
                    'status' => MinuteApprovalStatus::Pending->value,
                    'approved_at' => null,
                    'rejected_at' => null,
                ],
            );
        }

        return $minute->fresh();
    }

    private function assertTenantAccess(User $actor, Minute $minute): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->tenant_id === null || (int) $actor->tenant_id !== (int) $minute->tenant_id) {
            throw ValidationException::withMessages([
                'tenant_id' => __('minutes.validation.tenant_mismatch'),
            ]);
        }
    }
}
