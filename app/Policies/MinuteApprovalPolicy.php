<?php

namespace App\Policies;

use App\Models\MinuteApproval;
use App\Models\User;

class MinuteApprovalPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null) {
            return false;
        }

        return $user->hasRole('tenant_admin') || $user->can('manage_minutes');
    }

    public function view(User $user, MinuteApproval $approval): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $approval->tenant_id) {
            return false;
        }

        if ($user->hasRole('tenant_admin') || $user->can('manage_minutes')) {
            return true;
        }

        return (int) $approval->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, MinuteApproval $approval): bool
    {
        // aprovação/rejeição são Actions, não edição direta.
        return false;
    }

    public function delete(User $user, MinuteApproval $approval): bool
    {
        return false;
    }
}

