<?php

namespace App\Policies;

use App\Enums\VoteStatus;
use App\Models\User;
use App\Models\Vote;

class VotePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null) {
            return false;
        }

        return $user->hasRole('tenant_admin')
            || $user->can('manage_votes')
            || $user->can('manage_meetings');
    }

    public function view(User $user, Vote $vote): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $vote->tenant_id) {
            return false;
        }

        if ($user->hasRole('tenant_admin') || $user->can('manage_votes')) {
            return true;
        }

        return $vote->meeting->participants()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null) {
            return false;
        }

        return $user->hasRole('tenant_admin') || $user->can('manage_votes');
    }

    public function update(User $user, Vote $vote): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $vote->tenant_id) {
            return false;
        }

        if (! ($user->hasRole('tenant_admin') || $user->can('manage_votes'))) {
            return false;
        }

        // edição só em draft (abertura/fecho/cancelamento são Actions)
        return $vote->status === VoteStatus::Draft;
    }

    public function delete(User $user, Vote $vote): bool
    {
        return $this->update($user, $vote);
    }

    public function restore(User $user, Vote $vote): bool
    {
        return $this->update($user, $vote);
    }

    public function forceDelete(User $user, Vote $vote): bool
    {
        return $this->update($user, $vote);
    }
}

