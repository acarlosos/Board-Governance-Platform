<?php

namespace App\Actions\Votes;

use App\Enums\VoteStatus;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Validation\ValidationException;

final class CancelVoteAction
{
    public function cancel(User $actor, Vote $vote): Vote
    {
        $this->assertTenantAccess($actor, $vote);

        if (! $vote->status->canTransitionTo(VoteStatus::Cancelled)) {
            throw ValidationException::withMessages([
                'status' => __('votes.validation.invalid_status_transition'),
            ]);
        }

        $vote->status = VoteStatus::Cancelled;
        $vote->save();

        return $vote->fresh();
    }

    private function assertTenantAccess(User $actor, Vote $vote): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->tenant_id === null || (int) $actor->tenant_id !== (int) $vote->tenant_id) {
            throw ValidationException::withMessages([
                'tenant_id' => __('votes.validation.tenant_mismatch'),
            ]);
        }
    }
}

