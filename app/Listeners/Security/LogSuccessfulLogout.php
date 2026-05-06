<?php

namespace App\Listeners\Security;

use App\Models\User;
use App\Services\Security\AuthSessionService;
use Illuminate\Auth\Events\Logout;

final class LogSuccessfulLogout
{
    public function __construct(
        private readonly AuthSessionService $authSessionService,
    ) {}

    public function handle(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->authSessionService->recordLogout($event->user);
    }
}
