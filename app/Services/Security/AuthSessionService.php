<?php

namespace App\Services\Security;

use App\Enums\AuditAction;
use App\Enums\AuthSessionStatus;
use App\Models\AuthSession;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AuthSessionService
{
    public function __construct(
        private readonly AuditLoggerService $audit,
    ) {}

    public function recordLogin(User $user, ?Request $request = null): AuthSession
    {
        $request ??= request();
        $sessionId = $this->resolveSessionId($request);

        $session = AuthSession::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->getKey(),
            'session_id' => $sessionId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'login_at' => now(),
            'last_activity_at' => now(),
            'status' => AuthSessionStatus::Active,
            'created_at' => now(),
        ]);

        $this->audit->log(
            action: AuditAction::Login,
            auditable: $session,
            actor: $user,
            tenantId: $user->tenant_id,
            request: $request,
        );

        return $session;
    }

    public function recordLogout(User $user, ?Request $request = null): void
    {
        $request ??= request();
        $sessionId = $this->resolveSessionId($request);

        $session = AuthSession::query()
            ->where('user_id', $user->getKey())
            ->where('status', AuthSessionStatus::Active->value)
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->latest('id')
            ->first();

        if ($session !== null) {
            $session->update([
                'status' => AuthSessionStatus::Closed,
                'logout_at' => now(),
                'last_activity_at' => now(),
            ]);

            $this->audit->log(
                action: AuditAction::Logout,
                auditable: $session,
                actor: $user,
                tenantId: $user->tenant_id,
                request: $request,
            );
        }
    }

    public function recordFailedLogin(?string $email, ?Request $request = null): void
    {
        $request ??= request();

        $user = $email ? User::query()->withoutGlobalScopes()->where('email', $email)->first() : null;

        $this->audit->log(
            action: AuditAction::FailedLogin,
            auditable: null,
            newValues: [
                'email' => $email,
            ],
            actor: $user,
            tenantId: $user?->tenant_id,
            request: $request,
        );
    }

    public function touchActivity(?string $sessionId): void
    {
        if (blank($sessionId)) {
            return;
        }

        $query = AuthSession::query()
            ->where('session_id', $sessionId)
            ->where('status', AuthSessionStatus::Active->value);

        // Defesa extra: mesmo com TenantScope, filtrar por tenant explicitamente quando possível.
        if (auth()->check()) {
            $user = auth()->user();
            if (! $user->shouldBypassTenantScope() && $user->tenant_id !== null) {
                $query->where('tenant_id', $user->tenant_id);
            }
        }

        $query->update(['last_activity_at' => now()]);
    }

    /**
     * Resolve uma sessão visível para um ator, aplicando escopo explícito
     * antes de qualquer autorização (anti-enumeração / anti cross-tenant).
     *
     * @throws ValidationException
     */
    public function resolveVisibleSessionForActor(User $actor, int|string $sessionId): AuthSession
    {
        $query = AuthSession::query()->whereKey($sessionId);

        if ($actor->isSuperAdmin()) {
            // visão global
        } elseif ($actor->hasRole('tenant_admin') || $actor->can('manage_security')) {
            $query->where('tenant_id', $actor->tenant_id);
        } else {
            $query->where('user_id', $actor->getKey());
        }

        /** @var AuthSession|null $session */
        $session = $query->first();

        if ($session === null) {
            throw ValidationException::withMessages([
                'session' => __('security.sessions.not_found'),
            ]);
        }

        return $session;
    }

    /**
     * Revoga uma sessão remota: marca como `closed` e remove o registo da
     * tabela `sessions` quando a aplicação usa `SESSION_DRIVER=database`.
     */
    public function revoke(AuthSession $session, User $actor): void
    {
        DB::transaction(function () use ($session, $actor): void {
            // Idempotente: nunca falhar ao revogar sessões já encerradas/expiradas.
            // Ainda removemos a row em `sessions` se existir (revogação remota real).
            if (filled($session->session_id) && $this->databaseSessionsAvailable()) {
                DB::table('sessions')->where('id', $session->session_id)->delete();
            }

            if ($session->status !== AuthSessionStatus::Active) {
                return;
            }

            $session->update([
                'status' => AuthSessionStatus::Closed,
                'logout_at' => $session->logout_at ?? now(),
            ]);

            $this->audit->log(
                action: AuditAction::SessionRevoked,
                auditable: $session,
                actor: $actor,
                tenantId: $session->tenant_id,
            );
        });
    }

    public function expire(AuthSession $session): void
    {
        if ($session->status !== AuthSessionStatus::Active) {
            return;
        }

        $session->update([
            'status' => AuthSessionStatus::Expired,
            'logout_at' => now(),
        ]);

        $this->audit->log(
            action: AuditAction::SessionExpired,
            auditable: $session,
            actor: null,
            tenantId: $session->tenant_id,
        );
    }

    private function resolveSessionId(?Request $request): ?string
    {
        if ($request === null) {
            return null;
        }

        try {
            return $request->hasSession() ? $request->session()->getId() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function databaseSessionsAvailable(): bool
    {
        return config('session.driver') === 'database';
    }
}
