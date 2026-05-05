<?php

namespace App\Policies;

use App\Enums\MinuteStatus;
use App\Models\Minute;
use App\Models\User;

class MinutePolicy
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
            || $user->can('manage_minutes')
            || $user->can('manage_meetings'); // participantes/gestores conseguem ver via filtros na query do resource
    }

    public function view(User $user, Minute $minute): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $minute->tenant_id) {
            return false;
        }

        if ($user->hasRole('tenant_admin') || $user->can('manage_minutes')) {
            return true;
        }

        return $minute->meeting->participants()
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

        return $user->hasRole('tenant_admin') || $user->can('manage_minutes');
    }

    public function update(User $user, Minute $minute): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $minute->tenant_id) {
            return false;
        }

        if (! ($user->hasRole('tenant_admin') || $user->can('manage_minutes'))) {
            return false;
        }

        // edição só em draft (workflow controlado por Actions)
        return $minute->status === MinuteStatus::Draft;
    }

    public function delete(User $user, Minute $minute): bool
    {
        return $this->update($user, $minute);
    }

    public function restore(User $user, Minute $minute): bool
    {
        return $this->update($user, $minute);
    }

    public function forceDelete(User $user, Minute $minute): bool
    {
        return $this->update($user, $minute);
    }
}

