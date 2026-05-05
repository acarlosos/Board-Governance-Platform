<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VoteOption;

class VoteOptionPolicy
{
    public function viewAny(User $user): bool
    {
        return (new VotePolicy)->viewAny($user);
    }

    public function view(User $user, VoteOption $option): bool
    {
        return (new VotePolicy)->view($user, $option->vote);
    }

    public function create(User $user): bool
    {
        return (new VotePolicy)->create($user);
    }

    public function update(User $user, VoteOption $option): bool
    {
        return (new VotePolicy)->update($user, $option->vote);
    }

    public function delete(User $user, VoteOption $option): bool
    {
        return (new VotePolicy)->update($user, $option->vote);
    }

    public function restore(User $user, VoteOption $option): bool
    {
        return (new VotePolicy)->update($user, $option->vote);
    }

    public function forceDelete(User $user, VoteOption $option): bool
    {
        return (new VotePolicy)->update($user, $option->vote);
    }
}

