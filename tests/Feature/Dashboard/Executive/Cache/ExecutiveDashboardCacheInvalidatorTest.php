<?php

namespace Tests\Feature\Dashboard\Executive\Cache;

use App\Enums\DashboardMetricsPeriod;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Dashboard\DashboardMetricsService;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheInvalidator;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheKeys;
use App\Services\Dashboard\Executive\ExecutiveDashboardReadService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExecutiveDashboardCacheInvalidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['board.dashboard.snapshot_version' => 'v1']);
        Cache::flush();
    }

    #[Test]
    public function test_invalidate_for_tenant_esquece_so_chaves_do_segmento(): void
    {
        $keysT1 = ExecutiveDashboardCacheKeys::allKeysForSegment('t_1');
        $keysT2 = ExecutiveDashboardCacheKeys::allKeysForSegment('t_2');
        foreach ($keysT1 as $k) {
            Cache::put($k, 'v1:'.$k, 600);
        }
        foreach ($keysT2 as $k) {
            Cache::put($k, 'v2:'.$k, 600);
        }

        $globalL1 = ExecutiveDashboardCacheKeys::l1Key('global', DashboardMetricsPeriod::ThisMonth);
        $noneL1 = ExecutiveDashboardCacheKeys::l1Key('none', DashboardMetricsPeriod::ThisMonth);
        Cache::put($globalL1, 'global-val', 600);
        Cache::put($noneL1, 'none-val', 600);

        app(ExecutiveDashboardCacheInvalidator::class)->invalidateForTenant(1);

        foreach ($keysT1 as $k) {
            $this->assertNull(Cache::get($k), 't_1 key '.$k.' deve ser esquecida.');
        }
        foreach ($keysT2 as $k) {
            $this->assertSame('v2:'.$k, Cache::get($k));
        }
        $this->assertSame('global-val', Cache::get($globalL1));
        $this->assertSame('none-val', Cache::get($noneL1));
    }

    #[Test]
    public function test_invalidate_for_tenant_nao_dispara_em_global_d11(): void
    {
        $globalL1 = ExecutiveDashboardCacheKeys::l1Key('global', DashboardMetricsPeriod::Last30Days);
        $globalL2 = ExecutiveDashboardCacheKeys::l2Key('global', DashboardMetricsPeriod::Last30Days);
        Cache::put($globalL1, ['g' => 1], 600);
        Cache::put($globalL2, ['hero' => []], 600);
        Cache::put(ExecutiveDashboardCacheInvalidator::FLEXIBLE_CREATED_PREFIX.$globalL2, time(), 600);

        app(ExecutiveDashboardCacheInvalidator::class)->invalidateForTenant(99);

        $this->assertSame(['g' => 1], Cache::get($globalL1));
        $this->assertSame(['hero' => []], Cache::get($globalL2));
        $this->assertNotNull(Cache::get(ExecutiveDashboardCacheInvalidator::FLEXIBLE_CREATED_PREFIX.$globalL2));
    }

    #[Test]
    public function test_apos_invalidate_get_metrics_volta_a_executar_sql(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $svc = app(DashboardMetricsService::class);
        $period = DashboardMetricsPeriod::ThisMonth;

        $svc->getMetrics($user, $period);
        app(ExecutiveDashboardCacheInvalidator::class)->invalidateForTenant((int) $tenant->id);

        try {
            DB::enableQueryLog();
            $svc->getMetrics($user, $period);
            $queriesAfterInvalidate = count(DB::getQueryLog());
            $this->assertGreaterThan(0, $queriesAfterInvalidate, 'L1 deve recalcular após invalidação.');
        } finally {
            DB::flushQueryLog();
            DB::disableQueryLog();
        }
    }

    #[Test]
    public function test_apos_invalidate_read_l2_recalcula_depois_segunda_leitura_usa_cache(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $read = app(ExecutiveDashboardReadService::class);
        $period = DashboardMetricsPeriod::ThisMonth;

        $read->read($user, $period);
        app(ExecutiveDashboardCacheInvalidator::class)->invalidateForTenant((int) $tenant->id);

        try {
            DB::enableQueryLog();
            $read->read($user, $period);
            $cold = count(DB::getQueryLog());
            DB::flushQueryLog();

            $read->read($user, $period);
            $warm = count(DB::getQueryLog());

            $this->assertGreaterThan(0, $cold);
            $this->assertLessThan($cold, $warm, 'Segunda leitura L2 após recálculo deve usar menos SQL.');
        } finally {
            DB::flushQueryLog();
            DB::disableQueryLog();
        }
    }
}
