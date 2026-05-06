<?php

namespace App\Actions\Security;

use App\Models\AuthSession;
use App\Models\User;
use App\Services\Security\AuthSessionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class RevokeAuthSessionAction
{
    public const RATE_LIMIT_MAX_ATTEMPTS = 30;

    public const RATE_LIMIT_DECAY_SECONDS = 60;

    public function __construct(
        private readonly AuthSessionService $authSessionService,
    ) {}

    public function executeById(User $actor, int|string $sessionId): void
    {
        $session = $this->authSessionService->resolveVisibleSessionForActor($actor, $sessionId);

        $this->execute($actor, $session);
    }

    public function execute(User $actor, AuthSession $session): void
    {
        $key = 'session-revoke:' . $actor->getKey();

        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'session' => __('security.sessions.rate_limited'),
            ]);
        }

        RateLimiter::hit($key, self::RATE_LIMIT_DECAY_SECONDS);

        if (! Gate::forUser($actor)->allows('revoke', $session)) {
            throw new AuthorizationException(__('security.sessions.unauthorized'));
        }

        $this->authSessionService->revoke($session, $actor);
    }
}
