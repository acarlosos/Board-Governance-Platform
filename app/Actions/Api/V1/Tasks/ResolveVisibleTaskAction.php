<?php

namespace App\Actions\Api\V1\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class ResolveVisibleTaskAction
{
    public function execute(User $actor, int $id): ?Task
    {
        $builder = Task::query()->withoutGlobalScopes()->whereKey($id);

        if (! $actor->isSuperAdmin()) {
            if ($actor->tenant_id === null) {
                return null;
            }

            $builder->where($builder->qualifyColumn('tenant_id'), $actor->tenant_id);
        }

        /** @var ?Task $task */
        $task = $builder->first();
        if ($task === null) {
            return null;
        }

        return Gate::forUser($actor)->allows('view', $task) ? $task : null;
    }
}

