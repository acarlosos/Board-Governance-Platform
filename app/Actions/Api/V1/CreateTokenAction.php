<?php

namespace App\Actions\Api\V1;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\Api\SanctumTokenService;
use App\Services\Api\TokenAbilityService;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class CreateTokenAction
{
    private const RATE_LIMIT_MAX_ATTEMPTS = 10;
    private const RATE_LIMIT_DECAY_SECONDS = 60;

    public function __construct(
        private readonly SanctumTokenService $tokens,
        private readonly TokenAbilityService $abilities,
        private readonly AuditLoggerService $audit,
    ) {}

    /**
     * @param  array{device_name: string, abilities?: list<string>}  $data
     * @return array{token: string, token_type: string, expires_at: ?string, token_record: array<string, mixed>}
     */
    public function execute(User $user, array $data): array
    {
        $key = 'api-token-create:' . $user->getKey();

        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'device_name' => ['Too many token creation attempts. Try again shortly.'],
            ]);
        }

        RateLimiter::hit($key, self::RATE_LIMIT_DECAY_SECONDS);

        $requested = $data['abilities'] ?? [];
        $finalAbilities = $this->abilities->intersectRequestedWithAllowedForUser($user, $requested);

        $newToken = DB::transaction(function () use ($user, $data, $finalAbilities) {
            return $this->tokens->createToken($user, (string) $data['device_name'], $finalAbilities);
        });

        $tokenModel = $newToken->accessToken;

        $this->audit->log(
            action: AuditAction::TokenCreated,
            auditable: null,
            newValues: [
                'token_id' => $tokenModel->getKey(),
                'device_name' => (string) $data['device_name'],
                'abilities' => $finalAbilities,
            ],
            actor: $user,
            tenantId: $user->tenant_id,
        );

        RateLimiter::clear($key);

        return [
            'token' => $newToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => optional($tokenModel->expires_at)->toISOString(),
            'token_record' => [
                'id' => (int) $tokenModel->getKey(),
                'name' => (string) $tokenModel->name,
                'abilities' => $tokenModel->abilities ?? [],
                'created_at' => optional($tokenModel->created_at)->toISOString(),
            ],
        ];
    }
}

