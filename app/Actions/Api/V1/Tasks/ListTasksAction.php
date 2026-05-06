<?php

namespace App\Actions\Api\V1\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListTasksAction
{
    /**
     * @param  array{per_page?: int, assigned_to?: string, status?: string, sort?: string, direction?: string}  $filters
     */
    public function execute(User $actor, array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min(100, $perPage));

        $assignedTo = trim((string) ($filters['assigned_to'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $sort = (string) ($filters['sort'] ?? 'created_at');
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $builder = Task::query()->withoutGlobalScopes();

        if (! $actor->isSuperAdmin()) {
            if ($actor->tenant_id === null) {
                $builder->whereRaw('0 = 1');
            } else {
                $builder->where($builder->qualifyColumn('tenant_id'), $actor->tenant_id);
            }
        }

        $isManager = $actor->isSuperAdmin() || $actor->hasRole('tenant_admin') || $actor->can('manage_tasks');

        if (! $isManager) {
            // Self-service: apenas tasks atribuídas ao próprio utilizador.
            $builder->where($builder->qualifyColumn('assigned_to'), $actor->getKey());
        } else {
            if ($assignedTo === 'me') {
                $builder->where($builder->qualifyColumn('assigned_to'), $actor->getKey());
            } elseif ($assignedTo !== '' && ctype_digit($assignedTo)) {
                $builder->where($builder->qualifyColumn('assigned_to'), (int) $assignedTo);
            }
        }

        if ($status !== '') {
            $builder->where($builder->qualifyColumn('status'), $status);
        }

        $allowedSort = ['created_at', 'due_date', 'priority', 'status'];
        if (! in_array($sort, $allowedSort, true)) {
            $sort = 'created_at';
        }

        $builder->orderBy($builder->qualifyColumn($sort), $direction);

        return $builder->paginate($perPage);
    }
}

