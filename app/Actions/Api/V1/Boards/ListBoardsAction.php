<?php

namespace App\Actions\Api\V1\Boards;

use App\Models\Board;
use App\Models\User;
use App\Support\Api\ApiSortParameter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListBoardsAction
{
    /**
     * @param  array{per_page?: int, page?: int, q?: string, status?: string, sort?: string}  $filters
     */
    public function execute(User $actor, array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min(100, $perPage));

        $q = trim((string) ($filters['q'] ?? ''));
        $status = isset($filters['status']) ? (string) $filters['status'] : '';
        $sortRaw = isset($filters['sort']) ? (string) $filters['sort'] : '';

        [$sortField, $direction] = ApiSortParameter::parse(
            $sortRaw !== '' ? $sortRaw : null,
            ['name', 'created_at'],
            'created_at',
            'desc'
        );

        $builder = Board::query()->withoutGlobalScopes();

        if (! $actor->isSuperAdmin()) {
            if ($actor->tenant_id === null) {
                $builder->whereRaw('0 = 1');
            } else {
                $builder->where($builder->qualifyColumn('tenant_id'), $actor->tenant_id);
            }
        }

        if (! $actor->isSuperAdmin() && ! $actor->hasRole('tenant_admin') && ! $actor->can('manage_boards')) {
            // board_member só vê boards onde é membro ativo
            $builder->whereHas('boardMembers', function ($q) use ($actor): void {
                $q->where('user_id', $actor->getKey())
                    ->where('status', 'active')
                    ->where('tenant_id', $actor->tenant_id);
            });
        }

        if ($q !== '') {
            $builder->where($builder->qualifyColumn('name'), 'like', '%'.$q.'%');
        }

        if ($status !== '') {
            $builder->where($builder->qualifyColumn('status'), $status);
        }

        $builder->orderBy($builder->qualifyColumn($sortField), $direction);

        return $builder->paginate($perPage);
    }
}
