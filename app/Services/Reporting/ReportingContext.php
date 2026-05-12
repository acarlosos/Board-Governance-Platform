<?php

namespace App\Services\Reporting;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Contexto de relatórios / métricas: resolve isolamento vs visão global (super_admin apenas).
 *
 * Todas as consultas devem usar {@see Model::query()->withoutGlobalScopes()} e depois
 * {@see self::restrictToTenant()} para aplicar tenant_id de forma explícita.
 * Motivo do par: retirar TenantScope antes de restrict — visão global do super_admin não filtra por tenant implícito; utilizadores tenant recebem tenant_id explícito (ou 0=1 se sem tenant).
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
        // reason: o builder deve estar sem TenantScope; aqui aplicamos tenant_id coerente com o segmento do contexto.
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
