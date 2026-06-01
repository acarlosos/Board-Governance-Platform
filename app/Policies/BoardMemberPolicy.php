<?php

namespace App\Policies;

use App\Models\BoardMember;
use App\Models\User;

class BoardMemberPolicy
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
            || $user->can('manage_boards')
            || $user->hasRole('board_member');
    }

    public function view(User $user, BoardMember $member): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $member->tenant_id) {
            return false;
        }

        if ($user->hasRole('tenant_admin') || $user->can('manage_boards')) {
            return true;
        }

        return $member->board->boardMembers()
            ->where('user_id', $user->id)
            ->where('status', 'active')
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

        return $user->hasRole('tenant_admin') || $user->can('manage_boards');
    }

    public function update(User $user, BoardMember $member): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $member->tenant_id) {
            return false;
        }

        return $user->hasRole('tenant_admin') || $user->can('manage_boards');
    }

    public function delete(User $user, BoardMember $member): bool
    {
        return $this->update($user, $member);
    }

    public function restore(User $user, BoardMember $member): bool
    {
        return $this->update($user, $member);
    }

    public function forceDelete(User $user, BoardMember $member): bool
    {
        return $this->update($user, $member);
    }
}
