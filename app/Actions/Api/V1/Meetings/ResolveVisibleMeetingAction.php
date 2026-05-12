<?php

namespace App\Actions\Api\V1\Meetings;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class ResolveVisibleMeetingAction
{
    public function execute(User $actor, int $id): ?Meeting
    {
        $builder = Meeting::query()->withoutGlobalScopes()->with(['board'])->whereKey($id); // reason: API v1 — strip TenantScope; visibilidade via tenant + policy.

        if (! $actor->isSuperAdmin()) {
            if ($actor->tenant_id === null) {
                return null;
            }

            $builder->where($builder->qualifyColumn('tenant_id'), $actor->tenant_id);
        }

        /** @var ?Meeting $meeting */
        $meeting = $builder->first();
        if ($meeting === null) {
            return null;
        }

        return Gate::forUser($actor)->allows('view', $meeting) ? $meeting : null;
    }
}

