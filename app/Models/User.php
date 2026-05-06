<?php

namespace App\Models;

use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'locale', 'tenant_id', 'status', 'is_super_admin'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'is_super_admin' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Role `super_admin` (Spatie) ou flag `is_super_admin` até consolidação única na Fase 2+.
     */
    public function isSuperAdmin(): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return $this->hasRole('super_admin');
    }

    public function hasTenantAccess(Tenant $tenant): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->tenant_id !== null && (int) $this->tenant_id === (int) $tenant->id;
    }

    /**
     * Bypass do TenantScope: alinhado com {@see self::isSuperAdmin()}.
     */
    public function shouldBypassTenantScope(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin';
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->two_factor_secret;
    }

    public function saveAppAuthenticationSecret(?string $secret): void
    {
        $this->two_factor_secret = $secret;
        $this->two_factor_confirmed_at = $secret !== null ? now() : null;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    /**
     * @return ?array<string>
     */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->two_factor_recovery_codes;
    }

    /**
     * @param  ?array<string>  $codes
     */
    public function saveAppAuthenticationRecoveryCodes(?array $codes): void
    {
        $this->two_factor_recovery_codes = $codes;
        $this->save();
    }

    public function hasTwoFactorEnabled(): bool
    {
        return filled($this->two_factor_secret);
    }
}
