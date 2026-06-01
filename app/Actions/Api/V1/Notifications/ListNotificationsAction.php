<?php

namespace App\Actions\Api\V1\Notifications;

use App\Models\NotificationCenter;
use App\Models\User;
use App\Support\Api\ApiSortParameter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListNotificationsAction
{
    /**
     * @param  array{
     *     per_page?: int,
     *     page?: int,
     *     unread?: bool,
     *     status?: string,
     *     channel?: string,
     *     sort?: string
     * }  $filters
     */
    public function execute(User $actor, array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min(100, $perPage));

        $unread = array_key_exists('unread', $filters) ? (bool) $filters['unread'] : null;
        $status = isset($filters['status']) ? (string) $filters['status'] : '';
        $channel = isset($filters['channel']) ? (string) $filters['channel'] : '';
        $sortRaw = isset($filters['sort']) ? (string) $filters['sort'] : '';

        [$sortField, $direction] = ApiSortParameter::parse(
            $sortRaw !== '' ? $sortRaw : null,
            ['created_at', 'read_at', 'sent_at'],
            'created_at',
            'desc'
        );

        $builder = NotificationCenter::query()->withoutGlobalScopes(); // reason: API v1 — strip TenantScope; tenant_id do actor no builder.

        if (! $actor->isSuperAdmin()) {
            if ($actor->tenant_id === null) {
                $builder->whereRaw('0 = 1');
            } else {
                $builder->where($builder->qualifyColumn('tenant_id'), $actor->tenant_id);
            }
        }

        $isManager = $actor->isSuperAdmin()
            || $actor->hasRole('tenant_admin')
            || $actor->can('manage_notifications')
            || $actor->can('manage_settings');

        if (! $isManager) {
            $builder->where($builder->qualifyColumn('user_id'), $actor->getKey());
        }

        if ($unread === true && $status === '') {
            $builder->whereNull($builder->qualifyColumn('read_at'));
        }

        if ($status !== '') {
            $builder->where($builder->qualifyColumn('status'), $status);
        }

        if ($channel !== '') {
            $builder->where($builder->qualifyColumn('channel'), $channel);
        }

        $builder->orderBy($builder->qualifyColumn($sortField), $direction);

        return $builder->paginate($perPage);
    }
}
