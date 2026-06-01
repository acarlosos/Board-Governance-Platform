<?php

namespace App\Policies;

use App\Models\MeetingParticipant;
use App\Models\User;

class MeetingParticipantPolicy
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
            || $user->can('manage_meetings')
            || $user->hasRole('board_member');
    }

    public function view(User $user, MeetingParticipant $participant): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $participant->tenant_id) {
            return false;
        }

        if ($user->hasRole('tenant_admin') || $user->can('manage_meetings')) {
            return true;
        }

        return $participant->meeting->participants()
            ->where('user_id', $user->id)
            ->exists()
            || $participant->meeting->board->boardMembers()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || $user->hasRole('board_member')) {
            return false;
        }

        return $user->hasRole('tenant_admin') || $user->can('manage_meetings');
    }

    public function update(User $user, MeetingParticipant $participant): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $participant->tenant_id) {
            return false;
        }

        return $user->hasRole('tenant_admin') || $user->can('manage_meetings');
    }

    public function delete(User $user, MeetingParticipant $participant): bool
    {
        return $this->update($user, $participant);
    }

    public function restore(User $user, MeetingParticipant $participant): bool
    {
        return $this->update($user, $participant);
    }

    public function forceDelete(User $user, MeetingParticipant $participant): bool
    {
        return $this->update($user, $participant);
    }
}
