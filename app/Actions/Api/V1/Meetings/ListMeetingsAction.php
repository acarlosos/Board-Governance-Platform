<?php

namespace App\Actions\Api\V1\Meetings;

use App\Models\Meeting;
use App\Models\User;
use App\Support\Api\ApiSortParameter;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListMeetingsAction
{
    /**
     * @param  array{
     *     per_page?: int,
     *     page?: int,
     *     q?: string,
     *     board_id?: int,
     *     date_from?: string,
     *     date_to?: string,
     *     status?: string,
     *     sort?: string
     * }  $filters
     */
    public function execute(User $actor, array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min(100, $perPage));

        $q = trim((string) ($filters['q'] ?? ''));
        $status = isset($filters['status']) ? (string) $filters['status'] : '';
        $sortRaw = isset($filters['sort']) ? (string) $filters['sort'] : '';
        $boardId = isset($filters['board_id']) ? (int) $filters['board_id'] : null;
        $dateFrom = isset($filters['date_from']) ? (string) $filters['date_from'] : '';
        $dateTo = isset($filters['date_to']) ? (string) $filters['date_to'] : '';

        [$sortField, $direction] = ApiSortParameter::parse(
            $sortRaw !== '' ? $sortRaw : null,
            ['scheduled_at', 'created_at'],
            'scheduled_at',
            'desc'
        );

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

        if ($boardId !== null && $boardId > 0) {
            $builder->where($builder->qualifyColumn('board_id'), $boardId);
        }

        $scheduledCol = $builder->qualifyColumn('scheduled_at');
        if ($dateFrom !== '' && $dateTo !== '') {
            $builder->whereBetween($scheduledCol, [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ]);
        } elseif ($dateFrom !== '') {
            $builder->where($scheduledCol, '>=', Carbon::parse($dateFrom)->startOfDay());
        } elseif ($dateTo !== '') {
            $builder->where($scheduledCol, '<=', Carbon::parse($dateTo)->endOfDay());
        }

        if ($q !== '') {
            $builder->where($builder->qualifyColumn('title'), 'like', '%'.$q.'%');
        }

        if ($status !== '') {
            $builder->where($builder->qualifyColumn('status'), $status);
        }

        $builder->orderBy($builder->qualifyColumn($sortField), $direction);

        return $builder->paginate($perPage);
    }
}
