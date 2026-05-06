<?php

namespace App\Listeners\Security;

use App\Models\User;
use App\Services\Security\AuthSessionService;
use Illuminate\Auth\Events\Login;

final class LogSuccessfulLogin
{
    public function __construct(
        private readonly AuthSessionService $authSessionService,
    ) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->authSessionService->recordLogin($event->user);
    }
}
