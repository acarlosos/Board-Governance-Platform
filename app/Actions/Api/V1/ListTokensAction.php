<?php

namespace App\Actions\Api\V1;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Laravel\Sanctum\PersonalAccessToken;

final class ListTokensAction
{
    public function execute(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return PersonalAccessToken::query()
            ->where('tokenable_type', $user->getMorphClass())
            ->where('tokenable_id', $user->getKey())
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}

