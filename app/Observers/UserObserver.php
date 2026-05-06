<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;

final class UserObserver
{
    /**
     * @var list<string>
     */
    private const AUDITABLE_FIELDS = [
        'name',
        'email',
        'tenant_id',
        'locale',
        'status',
        'is_super_admin',
    ];

    public function created(User $user): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Created,
            $user,
            oldValues: [],
            newValues: $this->onlyAllowed($user->getAttributes()),
            tenantId: $user->tenant_id,
        );
    }

    public function updated(User $user): void
    {
        $this->logTwoFactorTransitions($user);
        $this->logPasswordChange($user);

        $changes = $this->onlyAllowed($user->getChanges());
        if ($changes === []) {
            return;
        }

        $original = [];
        foreach (array_keys($changes) as $key) {
            $original[$key] = $user->getOriginal($key);
        }

        $action = array_key_exists('status', $changes)
            ? AuditAction::StatusChanged
            : AuditAction::Updated;

        app(AuditLoggerService::class)->log(
            $action,
            $user,
            oldValues: $original,
            newValues: $changes,
            tenantId: $user->tenant_id,
        );
    }

    public function deleted(User $user): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Deleted,
            $user,
            oldValues: $this->onlyAllowed($user->getOriginal()),
            newValues: [],
            tenantId: $user->tenant_id,
        );
    }

    public function restored(User $user): void
    {
        app(AuditLoggerService::class)->log(
            AuditAction::Restored,
            $user,
            oldValues: [],
            newValues: $this->onlyAllowed($user->getAttributes()),
            tenantId: $user->tenant_id,
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function onlyAllowed(array $values): array
    {
        return array_intersect_key($values, array_flip(self::AUDITABLE_FIELDS));
    }

    private function logTwoFactorTransitions(User $user): void
    {
        $changes = $user->getChanges();

        if (! array_key_exists('two_factor_secret', $changes)) {
            return;
        }

        $previous = $user->getOriginal('two_factor_secret');
        $current = $changes['two_factor_secret'] ?? null;

        if (blank($previous) && filled($current)) {
            $action = AuditAction::TwoFactorEnabled;
        } elseif (filled($previous) && blank($current)) {
            $action = AuditAction::TwoFactorDisabled;
        } else {
            return;
        }

        app(AuditLoggerService::class)->log(
            $action,
            $user,
            oldValues: [],
            newValues: ['two_factor_changed' => true],
            tenantId: $user->tenant_id,
        );
    }

    private function logPasswordChange(User $user): void
    {
        $changes = $user->getChanges();

        if (! array_key_exists('password', $changes)) {
            return;
        }

        app(AuditLoggerService::class)->log(
            AuditAction::PasswordChanged,
            $user,
            oldValues: [],
            newValues: ['password_changed' => true],
            tenantId: $user->tenant_id,
        );
    }
}
