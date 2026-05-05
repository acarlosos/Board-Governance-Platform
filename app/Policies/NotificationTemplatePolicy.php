<?php

namespace App\Policies;

use App\Models\NotificationTemplate;
use App\Models\User;

class NotificationTemplatePolicy
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
            || $user->can('manage_notifications')
            || $user->can('manage_settings');
    }

    public function view(User $user, NotificationTemplate $template): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null) {
            return false;
        }

        // tenant pode ver templates globais (fallback)
        if ($template->tenant_id === null) {
            return $this->viewAny($user);
        }

        if ((int) $template->tenant_id !== (int) $user->tenant_id) {
            return false;
        }

        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, NotificationTemplate $template): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null) {
            return false;
        }

        // tenant_admin não edita template global
        if ($template->tenant_id === null) {
            return false;
        }

        if ((int) $template->tenant_id !== (int) $user->tenant_id) {
            return false;
        }

        return $user->hasRole('tenant_admin')
            || $user->can('manage_notifications')
            || $user->can('manage_settings');
    }

    public function delete(User $user, NotificationTemplate $template): bool
    {
        return $this->update($user, $template);
    }

    public function restore(User $user, NotificationTemplate $template): bool
    {
        return $this->update($user, $template);
    }

    public function forceDelete(User $user, NotificationTemplate $template): bool
    {
        return $this->update($user, $template);
    }
}

