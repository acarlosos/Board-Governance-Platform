<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
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
            || $user->can('manage_tasks');
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $task->tenant_id) {
            return false;
        }

        if ($user->hasRole('tenant_admin') || $user->can('manage_tasks')) {
            return true;
        }

        if ((int) $task->assigned_to === (int) $user->id) {
            return true;
        }

        // Opcional: visível para qualquer usuário do tenant se estiver relacionado a algo que ele já consegue ver
        if ($task->related) {
            return $user->can('view', $task->related);
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null) {
            return false;
        }

        return $user->hasRole('tenant_admin') || $user->can('manage_tasks');
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $task->tenant_id) {
            return false;
        }

        if ($user->hasRole('tenant_admin') || $user->can('manage_tasks')) {
            return true;
        }

        // assigned user pode atualizar status via Actions (sem edição livre de campos)
        return (int) $task->assigned_to === (int) $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->create($user);
    }

    public function restore(User $user, Task $task): bool
    {
        return $this->create($user);
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return $this->create($user);
    }
}

