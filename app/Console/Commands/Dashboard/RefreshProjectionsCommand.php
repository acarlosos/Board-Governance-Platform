<?php

namespace App\Console\Commands\Dashboard;

use App\Enums\DashboardMetricsPeriod;
use App\Jobs\Dashboard\RefreshTenantDashboardSnapshotJob;
use App\Models\Tenant;
use App\Models\TenantDashboardSnapshot;
use Illuminate\Console\Command;

final class RefreshProjectionsCommand extends Command
{
    protected $signature = 'dashboard:refresh-projections
                            {--tenant= : ID do tenant}
                            {--force : Refrescar mesmo válidos}';

    protected $description = 'Dispacha refresh das projections L3 (Hero+Operations) do dashboard executivo.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $dispatched = 0;

        if ($this->option('tenant') !== null && $this->option('tenant') !== '') {
            $tenantId = (int) $this->option('tenant');
            foreach (DashboardMetricsPeriod::filterOptions() as $period) {
                if ($this->shouldDispatch($tenantId, $period, $force)) {
                    RefreshTenantDashboardSnapshotJob::dispatch($tenantId, $period);
                    $dispatched++;
                }
            }
            $this->info("Dispatched {$dispatched} job(s) for tenant {$tenantId}.");

            return self::SUCCESS;
        }

        Tenant::query()->withoutGlobalScopes()->orderBy('id')->each(function (Tenant $tenant) use ($force, &$dispatched): void { // reason: comando consola percorre todos os tenants sem contexto HTTP.
            foreach (DashboardMetricsPeriod::filterOptions() as $period) {
                if ($this->shouldDispatch((int) $tenant->id, $period, $force)) {
                    RefreshTenantDashboardSnapshotJob::dispatch((int) $tenant->id, $period);
                    $dispatched++;
                }
            }
        });

        $this->info("Dispatched {$dispatched} job(s) total.");

        return self::SUCCESS;
    }

    private function shouldDispatch(int $tenantId, DashboardMetricsPeriod $period, bool $force): bool
    {
        if ($force) {
            return true;
        }

        $row = TenantDashboardSnapshot::query()->withoutGlobalScopes() // reason: leitura por tenant_id no comando; sem scope de request.
            ->where('tenant_id', $tenantId)
            ->where('period', $period->value)
            ->first();

        if ($row === null) {
            return true;
        }

        if ($row->is_stale) {
            return true;
        }

        if ($row->refreshed_at === null) {
            return true;
        }

        return $row->refreshed_at->lt(now()->subMinutes(10));
    }
}
