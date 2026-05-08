<?php

namespace App\Services\Api;

use App\Models\User;

final class TokenAbilityService
{
    public const AUTH_READ = 'auth:read';
    public const TOKENS_READ_SELF = 'tokens:read:self';
    public const TOKENS_MANAGE_SELF = 'tokens:manage:self';
    public const BOARDS_READ = 'boards:read';
    public const MEETINGS_READ = 'meetings:read';
    public const TASKS_READ = 'tasks:read';
    public const TASKS_WRITE = 'tasks:write';
    public const NOTIFICATIONS_READ = 'notifications:read';
    public const NOTIFICATIONS_WRITE = 'notifications:write';

    /**
     * @return list<string>
     */
    public function defaultLoginAbilities(): array
    {
        return [
            self::AUTH_READ,
            self::TOKENS_READ_SELF,
        ];
    }

    /**
     * @return list<string>
     */
    public function supportedAbilities(): array
    {
        return [
            self::AUTH_READ,
            self::TOKENS_READ_SELF,
            self::TOKENS_MANAGE_SELF,
            self::BOARDS_READ,
            self::MEETINGS_READ,
            self::TASKS_READ,
            self::TASKS_WRITE,
            self::NOTIFICATIONS_READ,
            self::NOTIFICATIONS_WRITE,
        ];
    }

    /**
     * @param  list<string>  $requested
     * @return list<string>
     */
    public function intersectRequestedWithAllowedForUser(User $user, array $requested): array
    {
        $supported = $this->supportedAbilities();
        $requested = array_values(array_unique(array_values(array_filter($requested, fn ($a): bool => is_string($a) && $a !== ''))));

        $allowed = array_values(array_intersect($requested, $supported));

        // Ensure tokens:manage:self implies tokens:read:self.
        if (in_array(self::TOKENS_MANAGE_SELF, $allowed, true) && ! in_array(self::TOKENS_READ_SELF, $allowed, true)) {
            $allowed[] = self::TOKENS_READ_SELF;
        }

        // Ensure *:write implies *:read.
        if (in_array(self::TASKS_WRITE, $allowed, true) && ! in_array(self::TASKS_READ, $allowed, true)) {
            $allowed[] = self::TASKS_READ;
        }
        if (in_array(self::NOTIFICATIONS_WRITE, $allowed, true) && ! in_array(self::NOTIFICATIONS_READ, $allowed, true)) {
            $allowed[] = self::NOTIFICATIONS_READ;
        }

        // Abilities do not grant privileges beyond user permissions; enforcement happens at route/action layer.
        return array_values(array_unique($allowed));
    }
}

