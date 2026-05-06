<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @property array{user: User, tenant: ?Tenant, token: ?PersonalAccessToken, capabilities: array<string, bool>} $resource
 */
final class MeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource['user'];

        /** @var ?Tenant $tenant */
        $tenant = $this->resource['tenant'] ?? null;

        /** @var ?PersonalAccessToken $token */
        $token = $this->resource['token'] ?? null;

        /** @var array<string, bool> $capabilities */
        $capabilities = $this->resource['capabilities'] ?? [];

        return [
            'user' => [
                'id' => (int) $user->getKey(),
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'tenant_id' => $user->tenant_id,
                'is_super_admin' => (bool) $user->isSuperAdmin(),
                'roles' => $user->getRoleNames()->values()->all(),
            ],
            'tenant' => $tenant ? [
                'id' => (int) $tenant->getKey(),
                'name' => (string) $tenant->name,
                'slug' => (string) $tenant->slug,
            ] : null,
            'token' => $token ? [
                'id' => (int) $token->getKey(),
                'name' => (string) $token->name,
                'abilities' => $token->abilities ?? [],
                'expires_at' => optional($token->expires_at)->toISOString(),
                'last_used_at' => optional($token->last_used_at)->toISOString(),
            ] : null,
            'capabilities' => $capabilities,
        ];
    }
}

