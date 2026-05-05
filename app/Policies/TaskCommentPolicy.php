<?php

namespace App\Policies;

use App\Models\TaskComment;
use App\Models\User;

class TaskCommentPolicy
{
    public function viewAny(User $user): bool
    {
        // relação manager ficará sob a Task (policy principal filtra)
        return $user->isSuperAdmin()
            || $user->hasRole('tenant_admin')
            || $user->can('manage_tasks');
    }

    public function view(User $user, TaskComment $comment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $comment->tenant_id) {
            return false;
        }

        // pode ver comentário se puder ver a task
        return $user->can('view', $comment->task);
    }

    public function create(User $user): bool
    {
        // criação de comentário é via Action e exige permissão na task
        return $user->tenant_id !== null;
    }

    public function update(User $user, TaskComment $comment): bool
    {
        return false;
    }

    public function delete(User $user, TaskComment $comment): bool
    {
        return false;
    }
}

