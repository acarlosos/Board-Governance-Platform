<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MeetingPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null) {
            return false;
        }

        return $user->hasRole('tenant_admin') || $user->can('manage_meetings') || $user->hasRole('board_member');
    }

    /**
     * API v1: endpoint GET /meetings — escopo de listagem aplicado na Action.
     */
    public function viewAnyInApi(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id !== null;
    }

    public function view(User $user, Meeting $meeting): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $meeting->tenant_id) {
            return false;
        }

        if ($user->hasRole('tenant_admin') || $user->can('manage_meetings')) {
            return true;
        }

        $isBoardMember = $meeting->board->boardMembers()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if ($isBoardMember) {
            return true;
        }

        return $meeting->participants()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // board_member pode visualizar, mas não gerir reuniões (mesmo que tenha permissões herdadas).
        if ($user->hasRole('board_member')) {
            return false;
        }

        return $user->tenant_id !== null && ($user->hasRole('tenant_admin') || $user->can('manage_meetings'));
    }

    public function update(User $user, Meeting $meeting): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $meeting->tenant_id) {
            return false;
        }

        if ($user->hasRole('board_member')) {
            return false;
        }

        return $user->hasRole('tenant_admin') || $user->can('manage_meetings');
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $this->update($user, $meeting);
    }

    public function restore(User $user, Meeting $meeting): bool
    {
        return $this->update($user, $meeting);
    }

    public function forceDelete(User $user, Meeting $meeting): bool
    {
        return $this->update($user, $meeting);
    }
}

