<?php

namespace App\Actions\Api\V1;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

final class RevokeTokenAction
{
    private const RATE_LIMIT_MAX_ATTEMPTS = 30;
    private const RATE_LIMIT_DECAY_SECONDS = 60;

    public function __construct(
        private readonly AuditLoggerService $audit,
    ) {}

    public function execute(User $actor, int $tokenId): bool
    {
        $key = 'api-token-revoke:' . $actor->getKey();

        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'token' => ['Too many token revocation attempts. Try again shortly.'],
            ]);
        }

        RateLimiter::hit($key, self::RATE_LIMIT_DECAY_SECONDS);

        /** @var ?PersonalAccessToken $token */
        $token = $this->resolveTokenForActor($actor, $tokenId);
        if ($token === null) {
            // Generic "not found" to avoid leaking existence of other users' tokens.
            return false;
        }

        DB::transaction(function () use ($actor, $token): void {
            $tokenId = $token->getKey();
            $token->delete();

            $this->audit->log(
                action: AuditAction::TokenRevoked,
                auditable: null,
                newValues: [
                    'token_id' => $tokenId,
                ],
                actor: $actor,
                tenantId: $actor->tenant_id,
            );
        });

        return true;
    }

    private function resolveTokenForActor(User $actor, int $tokenId): ?PersonalAccessToken
    {
        $query = PersonalAccessToken::query()->whereKey($tokenId);

        if (! $actor->isSuperAdmin()) {
            $query->where('tokenable_type', $actor->getMorphClass())
                ->where('tokenable_id', $actor->getKey());
        }

        /** @var ?PersonalAccessToken $token */
        $token = $query->first();

        return $token;
    }
}

