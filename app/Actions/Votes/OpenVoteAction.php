<?php

namespace App\Actions\Votes;

use App\Enums\VoteStatus;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Validation\ValidationException;

final class OpenVoteAction
{
    public function open(User $actor, Vote $vote): Vote
    {
        $this->assertTenantAccess($actor, $vote);

        if (! $vote->status->canTransitionTo(VoteStatus::Open)) {
            throw ValidationException::withMessages([
                'status' => __('votes.validation.invalid_status_transition'),
            ]);
        }

        if ($vote->options()->count() < 2) {
            throw ValidationException::withMessages([
                'options' => __('votes.validation.open_requires_two_options'),
            ]);
        }

        $vote->status = VoteStatus::Open;
        $vote->save();

        $vote = $vote->fresh();

        app(NotifyVoteOpenedParticipantsAction::class)->notify($actor, $vote);

        return $vote;
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
