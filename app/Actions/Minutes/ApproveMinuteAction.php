<?php

namespace App\Actions\Minutes;

use App\Enums\MinuteApprovalStatus;
use App\Enums\MinuteStatus;
use App\Models\Minute;
use App\Models\MinuteApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ApproveMinuteAction
{
    /**
     * @param  array{comments?:string|null}  $data
     */
    public function approve(User $actor, Minute $minute, array $data = []): Minute
    {
        $this->assertTenantAccess($actor, $minute);

        if ($minute->status !== MinuteStatus::InReview) {
            throw ValidationException::withMessages([
                'status' => __('minutes.validation.approve_only_in_review'),
            ]);
        }

        return DB::transaction(function () use ($actor, $minute, $data): Minute {
            /** @var MinuteApproval|null $approval */
            $approval = $minute->approvals()
                ->where('user_id', $actor->id)
                ->first();

            if (! $approval) {
                throw ValidationException::withMessages([
                    'user_id' => __('minutes.validation.not_eligible_to_approve'),
                ]);
            }

            if ($approval->status === MinuteApprovalStatus::Approved) {
                throw ValidationException::withMessages([
                    'status' => __('minutes.validation.already_approved'),
                ]);
            }

            $approval->status = MinuteApprovalStatus::Approved;
            $approval->approved_at = now();
            $approval->rejected_at = null;
            $approval->comments = $data['comments'] ?? null;
            $approval->save();

            $allApproved = $minute->approvals()
                ->where('status', '!=', MinuteApprovalStatus::Approved->value)
                ->doesntExist();

            if ($allApproved) {
                if (! $minute->status->canTransitionTo(MinuteStatus::Approved)) {
                    throw ValidationException::withMessages([
                        'status' => __('minutes.validation.invalid_status_transition'),
                    ]);
                }

                $minute->status = MinuteStatus::Approved;
                $minute->save();
            }

            return $minute->fresh();
        });
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

