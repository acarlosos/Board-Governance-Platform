<?php

namespace App\Services\Api;

use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\NewAccessToken;

final class SanctumTokenService
{
    /**
     * @param  list<string>  $abilities
     */
    public function createToken(User $user, string $deviceName, array $abilities): NewAccessToken
    {
        $expiresAt = $this->defaultExpiresAt();

        return $user->createToken($deviceName, $abilities, $expiresAt);
    }

    public function defaultExpiresAt(): ?Carbon
    {
        $days = (int) config('board.api.token_expiration_days', 30);

        return $days > 0 ? now()->addDays($days) : null;
    }
}

