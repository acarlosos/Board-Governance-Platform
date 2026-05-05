<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\MeetingAgendaItem;
use App\Models\User;

class MeetingAgendaItemPolicy
{
    public function viewAny(User $user, Meeting $meeting): bool
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

    public function view(User $user, MeetingAgendaItem $item): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $item->tenant_id) {
            return false;
        }

        if ($user->hasRole('tenant_admin') || $user->can('manage_meetings')) {
            return true;
        }

        return $item->meeting->participants()
            ->where('user_id', $user->id)
            ->exists()
            || $item->meeting->board->boardMembers()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();
    }

    public function create(User $user, Meeting $meeting): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $meeting->tenant_id) {
            return false;
        }

        return $user->hasRole('tenant_admin') || $user->can('manage_meetings');
    }

    public function update(User $user, MeetingAgendaItem $item): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $item->tenant_id) {
            return false;
        }

        return $user->hasRole('tenant_admin') || $user->can('manage_meetings');
    }

    public function delete(User $user, MeetingAgendaItem $item): bool
    {
        return $this->update($user, $item);
    }

    public function restore(User $user, MeetingAgendaItem $item): bool
    {
        return $this->update($user, $item);
    }

    public function forceDelete(User $user, MeetingAgendaItem $item): bool
    {
        return $this->update($user, $item);
    }
}

