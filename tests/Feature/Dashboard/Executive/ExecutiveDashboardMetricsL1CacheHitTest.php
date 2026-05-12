<?php

namespace Tests\Feature\Dashboard\Executive;

use App\Enums\DashboardMetricsPeriod;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Dashboard\DashboardMetricsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExecutiveDashboardMetricsL1CacheHitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
    }

    #[Test]
    public function test_segunda_leitura_de_metricas_usa_cache_l1_com_menos_queries(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $svc = app(DashboardMetricsService::class);
        $period = DashboardMetricsPeriod::ThisMonth;

        try {
            DB::enableQueryLog();
            $svc->getMetrics($user, $period);
            $first = count(DB::getQueryLog());
            DB::flushQueryLog();

            $svc->getMetrics($user, $period);
            $second = count(DB::getQueryLog());

            $this->assertLessThan($first, $second, 'Segunda chamada deve bater no L1 e executar menos SQL.');
        } finally {
            DB::flushQueryLog();
            DB::disableQueryLog();
        }
    }
}
