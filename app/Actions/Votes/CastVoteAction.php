<?php

namespace App\Actions\Votes;

use App\Enums\VoteStatus;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteOption;
use App\Models\VoteResponse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CastVoteAction
{
    /**
     * @param  array{vote_option_id?:int|null, comment?:string|null}  $data
     */
    public function cast(User $actor, Vote $vote, array $data): VoteResponse
    {
        $this->assertTenantAccess($actor, $vote);

        if ($actor->tenant_id === null) {
            throw ValidationException::withMessages([
                'tenant_id' => __('votes.validation.tenant_required'),
            ]);
        }

        $isParticipant = $vote->meeting->participants()
            ->where('user_id', $actor->id)
            ->exists();

        if (! $isParticipant) {
            throw ValidationException::withMessages([
                'user_id' => __('votes.validation.only_participants_can_vote'),
            ]);
        }

        if ($vote->status !== VoteStatus::Open) {
            throw ValidationException::withMessages([
                'status' => __('votes.validation.vote_not_open'),
            ]);
        }

        $now = now();
        if ($vote->starts_at && $now->lt($vote->starts_at)) {
            throw ValidationException::withMessages([
                'starts_at' => __('votes.validation.outside_voting_period'),
            ]);
        }
        if ($vote->ends_at && $now->gt($vote->ends_at)) {
            throw ValidationException::withMessages([
                'ends_at' => __('votes.validation.outside_voting_period'),
            ]);
        }

        $optionId = $data['vote_option_id'] ?? null;
        if ($optionId !== null) {
            $option = VoteOption::query()
                ->withoutGlobalScopes() // reason: validar opção por id com vínculo explícito ao vote; evita falsos negativos do scope.
                ->whereKey($optionId)
                ->first();

            if (! $option || (int) $option->vote_id !== (int) $vote->id) {
                throw ValidationException::withMessages([
                    'vote_option_id' => __('votes.validation.option_must_belong_to_vote'),
                ]);
            }
        }

        try {
            return DB::transaction(function () use ($actor, $vote, $optionId, $data): VoteResponse {
                $response = VoteResponse::query()->create([
                    'tenant_id' => $vote->tenant_id,
                    'vote_id' => $vote->id,
                    'vote_option_id' => $optionId,
                    'user_id' => $actor->id,
                    'comment' => $data['comment'] ?? null,
                    'voted_at' => now(),
                ]);

                return $response->fresh();
            });
        } catch (QueryException $e) {
            // unique(tenant_id, vote_id, user_id)
            throw ValidationException::withMessages([
                'user_id' => __('votes.validation.already_voted'),
            ]);
        }
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

