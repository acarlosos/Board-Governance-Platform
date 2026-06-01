<?php

namespace App\Services\Dashboard\Executive\Projection;

use App\Enums\DashboardMetricsPeriod;
use App\Models\TenantDashboardSnapshot;
use App\Models\User;
use App\Services\Dashboard\Executive\Providers\HeroProvider;
use App\Services\Dashboard\Executive\Providers\OperationsProvider;

final class DashboardProjectionService
{
    public function __construct(
        private readonly HeroProvider $hero,
        private readonly OperationsProvider $operations,
    ) {}

    public function refreshFor(int $tenantId, DashboardMetricsPeriod $period): void
    {
        $user = User::query()->withoutGlobalScopes() // reason: job sem request; filtra tenant_id explicitamente abaixo.
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->first();

        if ($user === null) {
            return;
        }

        $version = (string) config('board.dashboard.snapshot_version', 'v1');

        $payload = [
            'version' => $version,
            'hero' => $this->hero->build($user, $period)->toArray(),
            'operations' => $this->operations->build($user, $period)->toArray(),
        ];

        TenantDashboardSnapshot::query()->withoutGlobalScopes()->updateOrCreate( // reason: escrita L3 por tenant_id; comando/job sem TenantScope HTTP.
            [
                'tenant_id' => $tenantId,
                'period' => $period->value,
            ],
            [
                'payload' => $payload,
                'is_stale' => false,
                'refreshed_at' => now(),
            ],
        );
    }

    public function findValid(int $tenantId, DashboardMetricsPeriod $period): ?TenantDashboardSnapshot
    {
        $snapshot = TenantDashboardSnapshot::query()
            ->withoutGlobalScopes() // reason: leitura L3 por tenant_id explícito; fora de request tenant-scoped.
            ->where('tenant_id', $tenantId)
            ->where('period', $period->value)
            ->valid()
            ->first();

        if ($snapshot === null) {
            return null;
        }

        $payload = $snapshot->payload;
        $payloadVersion = is_array($payload) ? ($payload['version'] ?? null) : null;
        if ($payloadVersion !== (string) config('board.dashboard.snapshot_version', 'v1')) {
            return null;
        }

        return $snapshot;
    }

    public function markStale(int $tenantId): void
    {
        TenantDashboardSnapshot::query()->withoutGlobalScopes() // reason: marca stale por tenant_id em job/comando sem TenantScope HTTP.
            ->where('tenant_id', $tenantId)
            ->update(['is_stale' => true]);
    }
}
