<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_users');
    }

    public function view(User $user, User $model): bool
    {
        if (! $user->can('manage_users')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id !== null
            && $model->tenant_id !== null
            && (int) $user->tenant_id === (int) $model->tenant_id;
    }

    public function create(User $user): bool
    {
        if (! $user->can('manage_users')) {
            return false;
        }

        return $user->isSuperAdmin() || $user->tenant_id !== null;
    }

    public function update(User $user, User $model): bool
    {
        return $this->view($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->view($user, $model);
    }
}
