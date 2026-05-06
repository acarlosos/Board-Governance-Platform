<?php

namespace App\Services\Reporting;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Contexto de relatórios / métricas: resolve isolamento vs visão global (super_admin apenas).
 *
 * Todas as consultas devem usar {@see Model::query()->withoutGlobalScopes()} e depois
 * {@see self::restrictToTenant()} para aplicar tenant_id de forma explícita.
 */
final class ReportingContext
{
    private function __construct(
        private readonly bool $isGlobalScope,
        private readonly ?int $tenantId,
    ) {}

    public static function fromUser(User $user): self
    {
        if ($user->shouldBypassTenantScope()) {
            return new self(isGlobalScope: true, tenantId: null);
        }

        if ($user->tenant_id === null) {
            return new self(isGlobalScope: false, tenantId: null);
        }

        return new self(isGlobalScope: false, tenantId: (int) $user->tenant_id);
    }

    public function isGlobalScope(): bool
    {
        return $this->isGlobalScope;
    }

    public function tenantId(): ?int
    {
        return $this->tenantId;
    }

    public function cacheSegment(): string
    {
        if ($this->isGlobalScope) {
            return 'global';
        }

        if ($this->tenantId !== null) {
            return 't_'.$this->tenantId;
        }

        return 'none';
    }

    /**
     * Aplica filtro de tenant no builder (já sem {@see \App\Models\Scopes\TenantScope}).
     */
    public function restrictToTenant(Builder $builder): void
    {
        if ($this->isGlobalScope) {
            return;
        }

        if ($this->tenantId !== null) {
            $builder->where($builder->qualifyColumn('tenant_id'), $this->tenantId);

            return;
        }

        $builder->whereRaw('0 = 1');
    }
}
