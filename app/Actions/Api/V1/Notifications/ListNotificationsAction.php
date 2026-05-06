<?php

namespace App\Actions\Api\V1\Notifications;

use App\Models\NotificationCenter;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListNotificationsAction
{
    /**
     * @param  array{per_page?: int, unread?: bool, sort?: string, direction?: string}  $filters
     */
    public function execute(User $actor, array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min(100, $perPage));

        $unread = array_key_exists('unread', $filters) ? (bool) $filters['unread'] : null;
        $sort = (string) ($filters['sort'] ?? 'created_at');
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $builder = NotificationCenter::query()->withoutGlobalScopes();

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

        if ($unread === true) {
            $builder->whereNull($builder->qualifyColumn('read_at'));
        }

        $allowedSort = ['created_at', 'read_at', 'sent_at'];
        if (! in_array($sort, $allowedSort, true)) {
            $sort = 'created_at';
        }

        $builder->orderBy($builder->qualifyColumn($sort), $direction);

        return $builder->paginate($perPage);
    }
}

