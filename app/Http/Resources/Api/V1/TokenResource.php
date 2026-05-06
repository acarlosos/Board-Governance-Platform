<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @property PersonalAccessToken $resource
 */
final class TokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PersonalAccessToken $token */
        $token = $this->resource;

        return [
            'id' => (int) $token->getKey(),
            'name' => (string) $token->name,
            'abilities' => $token->abilities ?? [],
            'created_at' => optional($token->created_at)->toISOString(),
            'last_used_at' => optional($token->last_used_at)->toISOString(),
            'expires_at' => optional($token->expires_at)->toISOString(),
        ];
    }
}

