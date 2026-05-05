<?php

namespace App\Policies;

use App\Models\IntegrationLog;
use App\Models\User;

class IntegrationLogPolicy
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
            || $user->can('manage_integrations')
            || $user->can('manage_settings');
    }

    public function view(User $user, IntegrationLog $log): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $log->tenant_id) {
            return false;
        }

        return $user->hasRole('tenant_admin')
            || $user->can('manage_integrations')
            || $user->can('manage_settings');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, IntegrationLog $log): bool
    {
        return false;
    }

    public function delete(User $user, IntegrationLog $log): bool
    {
        return false;
    }
}

