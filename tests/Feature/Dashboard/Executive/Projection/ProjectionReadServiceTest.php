<?php

namespace Tests\Feature\Dashboard\Executive\Projection;

use App\Enums\DashboardMetricsPeriod;
use App\Models\Tenant;
use App\Models\TenantDashboardSnapshot;
use App\Models\User;
use App\Services\Dashboard\DashboardMetricsService;
use App\Services\Dashboard\Executive\ExecutiveDashboardReadService;
use App\Services\Dashboard\Executive\Observability\ExecutiveDashboardObservability;
use App\Services\Dashboard\Executive\Projection\DashboardProjectionService;
use App\Services\Dashboard\Executive\Providers\ActivityFeedProvider;
use App\Services\Dashboard\Executive\Providers\HeroProvider;
use App\Services\Dashboard\Executive\Providers\KpiStripProvider;
use App\Services\Dashboard\Executive\Providers\OperationsProvider;
use App\Services\Dashboard\Executive\Providers\PrioritiesProvider;
use App\Services\Dashboard\Executive\Snapshot\HeroSummary;
use App\Services\Dashboard\Executive\Snapshot\OperationsBlock;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectionReadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * @return array{version: string, hero: array<string, mixed>, operations: array<string, mixed>}
     */
    private function projectionPayload(string $version = 'v1'): array
    {
        return [
            'version' => $version,
            'hero' => [
                'tasks_overdue' => 3,
                'votes_open' => 0,
                'signatures_pending' => 0,
                'next_meeting_at' => null,
                'next_meeting_id' => null,
            ],
            'operations' => [
                'minutes_pending_review' => 0,
                'meetings_this_month' => 0,
                'notifications_unread' => 0,
            ],
        ];
    }

    /**
     * @return array{hero: array<string, mixed>, operations: array<string, mixed>}
     */
    private function l2PlainFromProjection(): array
    {
        $p = $this->projectionPayload();

        return [
            'hero' => $p['hero'],
            'operations' => $p['operations'],
        ];
    }

    private function readServiceWithMockCache(CacheRepository $cache): ExecutiveDashboardReadService
    {
        return new ExecutiveDashboardReadService(
            hero: new HeroProvider,
            kpi: new KpiStripProvider(app(DashboardMetricsService::class)),
            operations: new OperationsProvider,
            priorities: new PrioritiesProvider,
            activity: new ActivityFeedProvider,
            cache: $cache,
            observability: app(ExecutiveDashboardObservability::class),
            projection: app(DashboardProjectionService::class),
        );
    }

    #[Test]
    public function test_use_projection_true_com_projection_valida_nao_chama_l2_flexible(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Config::set([
            'board.dashboard.use_projection' => true,
            'board.dashboard.snapshot_version' => 'v1',
        ]);

        try {
            $tenant = Tenant::factory()->create();
            $actor = User::factory()->create(['tenant_id' => $tenant->id]);
            $actor->assignRole('tenant_admin');

            TenantDashboardSnapshot::factory()->create([
                'tenant_id' => $tenant->id,
                'period' => 'this_month',
                'payload' => $this->projectionPayload(),
                'is_stale' => false,
                'refreshed_at' => now(),
            ]);

            $cache = Mockery::mock(CacheRepository::class);
            $cache->shouldReceive('has')->never();
            $cache->shouldReceive('flexible')->never();

            $snapshot = $this->readServiceWithMockCache($cache)->read($actor, DashboardMetricsPeriod::ThisMonth);

            $this->assertEquals(new HeroSummary(3, 0, 0, null, null), $snapshot->hero);
            $this->assertEquals(new OperationsBlock(0, 0, 0), $snapshot->operations);
        } finally {
            Config::set(['board.dashboard.use_projection' => false]);
        }
    }

    #[Test]
    public function test_use_projection_true_sem_projection_fallback_l2_sem_auto_refresh_job(): void
    {
        Bus::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        Config::set([
            'board.dashboard.use_projection' => true,
            'board.dashboard.snapshot_version' => 'v1',
        ]);

        try {
            $tenant = Tenant::factory()->create();
            $actor = User::factory()->create(['tenant_id' => $tenant->id]);
            $actor->assignRole('tenant_admin');

            $plain = $this->l2PlainFromProjection();
            $cache = Mockery::mock(CacheRepository::class);
            $cache->shouldReceive('has')->once()->andReturn(false);
            $cache->shouldReceive('flexible')->once()->andReturn($plain);

            $this->readServiceWithMockCache($cache)->read($actor, DashboardMetricsPeriod::ThisMonth);

            Bus::assertNothingDispatched();
        } finally {
            Config::set(['board.dashboard.use_projection' => false]);
        }
    }

    #[Test]
    public function test_use_projection_true_com_is_stale_fallback_l2(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Config::set([
            'board.dashboard.use_projection' => true,
            'board.dashboard.snapshot_version' => 'v1',
        ]);

        try {
            $tenant = Tenant::factory()->create();
            $actor = User::factory()->create(['tenant_id' => $tenant->id]);
            $actor->assignRole('tenant_admin');

            TenantDashboardSnapshot::factory()->create([
                'tenant_id' => $tenant->id,
                'period' => 'this_month',
                'payload' => $this->projectionPayload(),
                'is_stale' => true,
                'refreshed_at' => now(),
            ]);

            $plain = $this->l2PlainFromProjection();
            $cache = Mockery::mock(CacheRepository::class);
            $cache->shouldReceive('has')->once()->andReturn(false);
            $cache->shouldReceive('flexible')->once()->andReturn($plain);

            $this->readServiceWithMockCache($cache)->read($actor, DashboardMetricsPeriod::ThisMonth);
        } finally {
            Config::set(['board.dashboard.use_projection' => false]);
        }
    }

    #[Test]
    public function test_use_projection_true_com_refreshed_antigo_fallback_l2(): void
    {
        Carbon::setTestNow('2026-03-10 12:00:00');
        $this->seed(RolesAndPermissionsSeeder::class);
        Config::set([
            'board.dashboard.use_projection' => true,
            'board.dashboard.snapshot_version' => 'v1',
        ]);

        try {
            $tenant = Tenant::factory()->create();
            $actor = User::factory()->create(['tenant_id' => $tenant->id]);
            $actor->assignRole('tenant_admin');

            TenantDashboardSnapshot::factory()->create([
                'tenant_id' => $tenant->id,
                'period' => 'this_month',
                'payload' => $this->projectionPayload(),
                'is_stale' => false,
                'refreshed_at' => now()->subMinutes(11),
            ]);

            $plain = $this->l2PlainFromProjection();
            $cache = Mockery::mock(CacheRepository::class);
            $cache->shouldReceive('has')->once()->andReturn(false);
            $cache->shouldReceive('flexible')->once()->andReturn($plain);

            $this->readServiceWithMockCache($cache)->read($actor, DashboardMetricsPeriod::ThisMonth);
        } finally {
            Config::set(['board.dashboard.use_projection' => false]);
            Carbon::setTestNow();
        }
    }

    #[Test]
    public function test_use_projection_false_ignora_projection_valida_e_usa_l2(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Config::set([
            'board.dashboard.use_projection' => false,
            'board.dashboard.snapshot_version' => 'v1',
        ]);

        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        TenantDashboardSnapshot::factory()->create([
            'tenant_id' => $tenant->id,
            'period' => 'this_month',
            'payload' => $this->projectionPayload(),
            'is_stale' => false,
            'refreshed_at' => now(),
        ]);

        $plain = $this->l2PlainFromProjection();
        $cache = Mockery::mock(CacheRepository::class);
        $cache->shouldReceive('has')->once()->andReturn(false);
        $cache->shouldReceive('flexible')->once()->andReturn($plain);

        $this->readServiceWithMockCache($cache)->read($actor, DashboardMetricsPeriod::ThisMonth);
    }
}
