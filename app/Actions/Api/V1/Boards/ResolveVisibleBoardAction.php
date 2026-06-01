<?php

namespace App\Actions\Api\V1\Boards;

use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class ResolveVisibleBoardAction
{
    public function execute(User $actor, int $id): ?Board
    {
        $builder = Board::query()->withoutGlobalScopes()->whereKey($id); // reason: API v1 — strip TenantScope; visibilidade via tenant + policy.

        if (! $actor->isSuperAdmin()) {
            if ($actor->tenant_id === null) {
                return null;
            }

            $builder->where($builder->qualifyColumn('tenant_id'), $actor->tenant_id);
        }

        /** @var ?Board $board */
        $board = $builder->first();
        if ($board === null) {
            return null;
        }

        return Gate::forUser($actor)->allows('view', $board) ? $board : null;
    }
}

