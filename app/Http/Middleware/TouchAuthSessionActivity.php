<?php

namespace App\Http\Middleware;

use App\Services\Security\AuthSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Atualiza `auth_sessions.last_activity_at` da sessão atual no máximo 1x/min.
 */
final class TouchAuthSessionActivity
{
    private const TOUCH_INTERVAL_SECONDS = 60;

    public function __construct(
        private readonly AuthSessionService $authSessionService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! auth()->check()) {
            return $response;
        }

        if (! $request->hasSession()) {
            return $response;
        }

        $session = $request->session();
        $sessionId = $session->getId();
        $cacheKey = '_auth_session_touched_at';
        $lastTouched = (int) ($session->get($cacheKey) ?? 0);

        if ((time() - $lastTouched) < self::TOUCH_INTERVAL_SECONDS) {
            return $response;
        }

        $session->put($cacheKey, time());
        $this->authSessionService->touchActivity($sessionId);

        return $response;
    }
}
