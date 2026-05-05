<?php

namespace App\Policies;

use App\Models\Integration;
use App\Models\User;

class IntegrationPolicy
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

    public function view(User $user, Integration $integration): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $integration->tenant_id) {
            return false;
        }

        return $user->hasRole('tenant_admin')
            || $user->can('manage_integrations')
            || $user->can('manage_settings');
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Integration $integration): bool
    {
        return $this->view($user, $integration);
    }

    public function delete(User $user, Integration $integration): bool
    {
        return $this->view($user, $integration);
    }

    public function restore(User $user, Integration $integration): bool
    {
        return $this->view($user, $integration);
    }

    public function forceDelete(User $user, Integration $integration): bool
    {
        return $this->view($user, $integration);
    }
}

