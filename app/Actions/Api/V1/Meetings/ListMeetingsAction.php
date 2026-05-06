<?php

namespace App\Actions\Api\V1\Meetings;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListMeetingsAction
{
    /**
     * @param  array{per_page?: int, q?: string, status?: string, sort?: string, direction?: string}  $filters
     */
    public function execute(User $actor, array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min(100, $perPage));

        $q = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $sort = (string) ($filters['sort'] ?? 'scheduled_at');
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $builder = Meeting::query()->withoutGlobalScopes()->with(['board']);

        if (! $actor->isSuperAdmin()) {
            if ($actor->tenant_id === null) {
                $builder->whereRaw('0 = 1');
            } else {
                $builder->where($builder->qualifyColumn('tenant_id'), $actor->tenant_id);
            }
        }

        if (! $actor->isSuperAdmin() && ! $actor->hasRole('tenant_admin') && ! $actor->can('manage_meetings')) {
            $tenantId = (int) $actor->tenant_id;

            $builder->where(function ($q) use ($actor, $tenantId): void {
                $q->whereHas('participants', function ($p) use ($actor, $tenantId): void {
                    $p->where('user_id', $actor->getKey())
                        ->where('tenant_id', $tenantId);
                })->orWhereHas('board.boardMembers', function ($bm) use ($actor, $tenantId): void {
                    $bm->where('user_id', $actor->getKey())
                        ->where('status', 'active')
                        ->where('tenant_id', $tenantId);
                });
            });
        }

        if ($q !== '') {
            $builder->where($builder->qualifyColumn('title'), 'like', '%'.$q.'%');
        }

        if ($status !== '') {
            $builder->where($builder->qualifyColumn('status'), $status);
        }

        $allowedSort = ['scheduled_at', 'created_at'];
        if (! in_array($sort, $allowedSort, true)) {
            $sort = 'scheduled_at';
        }

        $builder->orderBy($builder->qualifyColumn($sort), $direction);

        return $builder->paginate($perPage);
    }
}

