<?php

namespace App\Policies;

use App\Models\NotificationCenter;
use App\Models\User;

class NotificationCenterPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null) {
            return false;
        }

        // Self-service: usuários do tenant podem listar as próprias notificações (API/UI aplica escopo).
        return true;
    }

    public function view(User $user, NotificationCenter $notification): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $notification->tenant_id) {
            return false;
        }

        if ($user->hasRole('tenant_admin') || $user->can('manage_notifications') || $user->can('manage_settings')) {
            return true;
        }

        return (int) $notification->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, NotificationCenter $notification): bool
    {
        return $this->view($user, $notification);
    }

    public function delete(User $user, NotificationCenter $notification): bool
    {
        return $user->isSuperAdmin()
            || ($user->tenant_id !== null
                && (int) $user->tenant_id === (int) $notification->tenant_id
                && ($user->hasRole('tenant_admin') || $user->can('manage_notifications') || $user->can('manage_settings')));
    }

    public function markAsRead(User $user, NotificationCenter $notification): bool
    {
        if (! $this->view($user, $notification)) {
            return false;
        }

        return (int) $notification->user_id === (int) $user->id || $user->isSuperAdmin();
    }
}

