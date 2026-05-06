<?php

namespace App\Services\Api;

use App\Models\User;

final class TokenAbilityService
{
    public const AUTH_READ = 'auth:read';
    public const TOKENS_READ_SELF = 'tokens:read:self';
    public const TOKENS_MANAGE_SELF = 'tokens:manage:self';

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

        // Abilities do not grant privileges beyond user permissions; enforcement happens at route/action layer.
        return array_values(array_unique($allowed));
    }
}

