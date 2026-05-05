<?php

namespace App\Actions\Votes;

use App\Enums\VoteStatus;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteOption;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class PersistVoteOptionAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, Vote $vote, array $data): VoteOption
    {
        $this->assertTenantAccess($actor, $vote);

        if ($vote->status !== VoteStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => __('votes.validation.options_only_in_draft'),
            ]);
        }

        $validator = Validator::make($data, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order_column' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated = $validator->validate();

        $option = new VoteOption;
        $option->fill(Arr::only($validated, ['title', 'description', 'order_column']));
        $option->tenant_id = $vote->tenant_id;
        $option->vote_id = $vote->id;
        $option->save();

        return $option->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, VoteOption $option, array $data): VoteOption
    {
        $this->assertTenantAccess($actor, $option->vote);

        if ($option->vote->status !== VoteStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => __('votes.validation.options_only_in_draft'),
            ]);
        }

        $validator = Validator::make($data, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order_column' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated = $validator->validate();

        $option->fill(Arr::only($validated, ['title', 'description', 'order_column']));
        $option->save();

        return $option->fresh();
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

