<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class CancelTaskAction
{
    public function cancel(User $actor, Task $task): Task
    {
        $this->assertTenantAccess($actor, $task);

        if (! $task->status->canTransitionTo(TaskStatus::Cancelled)) {
            throw ValidationException::withMessages([
                'status' => __('tasks.validation.invalid_status_transition'),
            ]);
        }

        $old = ['status' => $task->status->value];

        $task->status = TaskStatus::Cancelled;
        $task->completed_at = null;
        $task->save();

        app(RecordTaskHistoryAction::class)->record($actor, $task, 'status_changed', $old, ['status' => $task->status->value]);

        return $task->fresh();
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

