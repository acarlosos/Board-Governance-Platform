<?php

namespace App\Actions\Api\V1;

use App\Models\User;
use App\Services\Api\CapabilitiesService;
use Laravel\Sanctum\PersonalAccessToken;

final class MeAction
{
    public function __construct(
        private readonly CapabilitiesService $capabilities,
    ) {}

    /**
     * @return array{user: User, tenant: ?\App\Models\Tenant, token: ?PersonalAccessToken, capabilities: array<string, bool>}
     */
    public function execute(User $user, ?PersonalAccessToken $token): array
    {
        return [
            'user' => $user,
            'tenant' => $user->tenant()->first(),
            'token' => $token,
            'capabilities' => $this->capabilities->forUser($user),
        ];
    }
}

