<?php

namespace Tests\Feature\Dashboard\Executive\Observability;

use App\Enums\DashboardMetricsPeriod;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Dashboard\DashboardMetricsService;
use App\Services\Dashboard\Executive\ExecutiveDashboardReadService;
use App\Services\Dashboard\Executive\Observability\ExecutiveDashboardObservability;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class L1L2InstrumentationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function test_l1_primeira_leitura_miss_segunda_hit(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-01 12:00:00', config('app.timezone')));

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $svc = app(DashboardMetricsService::class);
        $period = DashboardMetricsPeriod::ThisMonth;

        $svc->getMetrics($user, $period);
        $snap1 = app(ExecutiveDashboardObservability::class)->snapshotFor(CarbonImmutable::parse('2026-06-01', config('app.timezone')));
        $this->assertSame(0, $snap1['l1']['hits']);
        $this->assertSame(1, $snap1['l1']['misses']);

        $svc->getMetrics($user, $period);
        $snap2 = app(ExecutiveDashboardObservability::class)->snapshotFor(CarbonImmutable::parse('2026-06-01', config('app.timezone')));
        $this->assertSame(1, $snap2['l1']['hits']);
        $this->assertSame(1, $snap2['l1']['misses']);

        CarbonImmutable::setTestNow();
    }

    #[Test]
    public function test_l2_primeira_leitura_miss_segunda_hit_e_none_sem_counters(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-02 12:00:00', config('app.timezone')));

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $read = app(ExecutiveDashboardReadService::class);
        $period = DashboardMetricsPeriod::ThisMonth;

        $read->read($user, $period);
        $snap1 = app(ExecutiveDashboardObservability::class)->snapshotFor(CarbonImmutable::parse('2026-06-02', config('app.timezone')));
        $this->assertSame(0, $snap1['l2']['hits']);
        $this->assertSame(1, $snap1['l2']['misses']);

        $read->read($user, $period);
        $snap2 = app(ExecutiveDashboardObservability::class)->snapshotFor(CarbonImmutable::parse('2026-06-02', config('app.timezone')));
        $this->assertSame(1, $snap2['l2']['hits']);
        $this->assertSame(1, $snap2['l2']['misses']);

        Cache::flush();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-03 12:00:00', config('app.timezone')));

        $noTenant = User::factory()->create(['tenant_id' => null]);
        $this->assertFalse($noTenant->isSuperAdmin());

        app(ExecutiveDashboardReadService::class)->read($noTenant, $period);
        $snapNone = app(ExecutiveDashboardObservability::class)->snapshotFor(CarbonImmutable::parse('2026-06-03', config('app.timezone')));
        $this->assertSame(0, $snapNone['l2']['hits']);
        $this->assertSame(0, $snapNone['l2']['misses']);

        CarbonImmutable::setTestNow();
    }
}
