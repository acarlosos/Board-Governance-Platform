<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class AddTaskCommentAction
{
    public function add(User $actor, Task $task, string $comment): TaskComment
    {
        $this->assertTenantAccess($actor, $task);

        if (! $actor->can('view', $task)) {
            throw ValidationException::withMessages([
                'task_id' => __('tasks.validation.not_allowed'),
            ]);
        }

        if (trim($comment) === '') {
            throw ValidationException::withMessages([
                'comment' => __('task-comments.validation.comment_required'),
            ]);
        }

        $created = TaskComment::query()->create([
            'tenant_id' => $task->tenant_id,
            'task_id' => $task->id,
            'user_id' => $actor->id,
            'comment' => $comment,
            'created_at' => now(),
        ]);

        app(RecordTaskHistoryAction::class)->record($actor, $task, 'comment_added', [], [
            'task_comment_id' => $created->id,
        ]);

        return $created->fresh();
    }

    private function assertTenantAccess(User $actor, Task $task): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->tenant_id === null || (int) $actor->tenant_id !== (int) $task->tenant_id) {
            throw ValidationException::withMessages([
                'tenant_id' => __('tasks.validation.tenant_mismatch'),
            ]);
        }
    }
}

