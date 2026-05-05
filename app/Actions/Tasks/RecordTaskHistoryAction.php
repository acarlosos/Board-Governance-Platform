<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\User;

final class RecordTaskHistoryAction
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function record(?User $actor, Task $task, string $action, array $oldValues = [], array $newValues = []): TaskHistory
    {
        return TaskHistory::query()->create([
            'tenant_id' => $task->tenant_id,
            'task_id' => $task->id,
            'action' => $action,
            'old_values' => empty($oldValues) ? null : $oldValues,
            'new_values' => empty($newValues) ? null : $newValues,
            'user_id' => $actor?->id,
            'created_at' => now(),
        ]);
    }
}

