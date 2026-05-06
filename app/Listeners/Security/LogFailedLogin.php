<?php

namespace App\Listeners\Security;

use App\Services\Security\AuthSessionService;
use Illuminate\Auth\Events\Failed;

final class LogFailedLogin
{
    public function __construct(
        private readonly AuthSessionService $authSessionService,
    ) {}

    public function handle(Failed $event): void
    {
        $email = null;
        $credentials = $event->credentials ?? [];

        if (is_array($credentials) && isset($credentials['email'])) {
            $email = (string) $credentials['email'];
        }

        $this->authSessionService->recordFailedLogin($email);
    }
}
