<?php

namespace App\Actions\Api\V1\Notifications;

use App\Models\NotificationCenter;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class ResolveVisibleNotificationAction
{
    public function execute(User $actor, int $id): ?NotificationCenter
    {
        $builder = NotificationCenter::query()->withoutGlobalScopes()->whereKey($id); // reason: API v1 — strip TenantScope; tenant do actor aplicado abaixo.

        if (! $actor->isSuperAdmin()) {
            if ($actor->tenant_id === null) {
                return null;
            }

            $builder->where($builder->qualifyColumn('tenant_id'), $actor->tenant_id);
        }

        /** @var ?NotificationCenter $notification */
        $notification = $builder->first();
        if ($notification === null) {
            return null;
        }

        return Gate::forUser($actor)->allows('view', $notification) ? $notification : null;
    }
}

