<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasRole('tenant_admin') && $user->tenant_id !== null;
    }

    public function view(User $user, AuditLog $log): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $log->tenant_id !== null
            && $user->tenant_id !== null
            && (int) $log->tenant_id === (int) $user->tenant_id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditLog $log): bool
    {
        return false;
    }

    public function delete(User $user, AuditLog $log): bool
    {
        return false;
    }

    public function restore(User $user, AuditLog $log): bool
    {
        return false;
    }

    public function forceDelete(User $user, AuditLog $log): bool
    {
        return false;
    }
}

