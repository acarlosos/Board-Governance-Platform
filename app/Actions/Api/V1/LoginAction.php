<?php

namespace App\Actions\Api\V1;

use App\Enums\AuditAction;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Api\SanctumTokenService;
use App\Services\Api\TokenAbilityService;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

final class LoginAction
{
    private const RATE_LIMIT_MAX_ATTEMPTS = 5;

    private const RATE_LIMIT_DECAY_SECONDS = 60;

    public function __construct(
        private readonly SanctumTokenService $tokens,
        private readonly TokenAbilityService $abilities,
        private readonly AuditLoggerService $audit,
    ) {}

    /**
     * @param  array{email: string, password: string, device_name: string, abilities?: list<string>}  $data
     * @return array{token: string, token_type: string, expires_at: ?string, user: array<string, mixed>, tenant: ?array<string, mixed>, abilities: list<string>}
     */
    public function execute(array $data): array
    {
        $email = mb_strtolower(trim($data['email']));
        $ip = (string) request()?->ip();

        $keyIp = 'api-login:ip:'.$ip;
        $keyEmail = 'api-login:email:'.$email;

        if (RateLimiter::tooManyAttempts($keyIp, self::RATE_LIMIT_MAX_ATTEMPTS)
            || RateLimiter::tooManyAttempts($keyEmail, self::RATE_LIMIT_MAX_ATTEMPTS)) {
            // 429 com envelope consistente via exception renderer (api/*).
            throw new TooManyRequestsHttpException;
        }

        RateLimiter::hit($keyIp, self::RATE_LIMIT_DECAY_SECONDS);
        RateLimiter::hit($keyEmail, self::RATE_LIMIT_DECAY_SECONDS);

        /** @var ?User $user */
        $user = User::query()->withoutGlobalScopes()->where('email', $email)->first(); // reason: API login resolve utilizador por email antes de estabelecer tenant na sessão.

        $hash = $user?->password ?? '$2y$10$'.str_repeat('a', 53);
        $ok = Hash::check((string) $data['password'], $hash) && $user !== null;

        if (! $ok) {
            $this->audit->log(
                action: AuditAction::ApiLogin,
                auditable: null,
                newValues: [
                    'success' => false,
                    'email' => $email,
                ],
                actor: $user,
                tenantId: $user?->tenant_id,
            );

            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $requested = $data['abilities'] ?? $this->abilities->defaultLoginAbilities();
        $finalAbilities = $this->abilities->intersectRequestedWithAllowedForUser($user, $requested);

        $newToken = DB::transaction(function () use ($user, $data, $finalAbilities) {
            return $this->tokens->createToken($user, (string) $data['device_name'], $finalAbilities);
        });

        /** @var ?Tenant $tenant */
        $tenant = $user->tenant()->first();
        $tokenModel = $newToken->accessToken;

        $this->audit->log(
            action: AuditAction::ApiLogin,
            auditable: null,
            newValues: [
                'success' => true,
                'device_name' => (string) $data['device_name'],
                'token_id' => $tokenModel->getKey(),
                'abilities' => $finalAbilities,
            ],
            actor: $user,
            tenantId: $user->tenant_id,
        );

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

        RateLimiter::clear($keyIp);
        RateLimiter::clear($keyEmail);

        return [
            'token' => $newToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => optional($tokenModel->expires_at)->toISOString(),
            'user' => [
                'id' => (int) $user->getKey(),
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'tenant_id' => $user->tenant_id,
                'is_super_admin' => (bool) $user->isSuperAdmin(),
            ],
            'tenant' => $tenant ? [
                'id' => (int) $tenant->getKey(),
                'name' => (string) $tenant->name,
                'slug' => (string) $tenant->slug,
            ] : null,
            'abilities' => $finalAbilities,
        ];
    }
}
