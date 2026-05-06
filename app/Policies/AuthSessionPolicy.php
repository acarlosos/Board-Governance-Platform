<?php

namespace App\Policies;

use App\Models\AuthSession;
use App\Models\User;

class AuthSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AuthSession $session): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($this->isSecurityManager($user)
            && $session->tenant_id !== null
            && $user->tenant_id !== null
            && (int) $session->tenant_id === (int) $user->tenant_id) {
            return true;
        }

        return $session->user_id !== null && (int) $session->user_id === (int) $user->getKey();
    }

    public function revoke(User $user, AuthSession $session): bool
    {
        return $this->view($user, $session);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuthSession $session): bool
    {
        return false;
    }

    public function delete(User $user, AuthSession $session): bool
    {
        return false;
    }

    private function isSecurityManager(User $user): bool
    {
        return $user->hasRole('tenant_admin') || $user->can('manage_security');
    }
}
