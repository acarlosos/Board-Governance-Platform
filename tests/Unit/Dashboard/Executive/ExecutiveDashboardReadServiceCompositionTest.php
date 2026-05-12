<?php

namespace Tests\Unit\Dashboard\Executive;

use App\Enums\DashboardMetricsPeriod;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Dashboard\DashboardMetricsService;
use App\Services\Dashboard\Executive\ExecutiveDashboardReadService;
use App\Services\Dashboard\Executive\Observability\ExecutiveDashboardObservability;
use App\Services\Dashboard\Executive\Providers\ActivityFeedProvider;
use App\Services\Dashboard\Executive\Providers\HeroProvider;
use App\Services\Dashboard\Executive\Providers\KpiStripProvider;
use App\Services\Dashboard\Executive\Providers\OperationsProvider;
use App\Services\Dashboard\Executive\Providers\PrioritiesProvider;
use App\Services\Dashboard\Executive\Snapshot\HeroSummary;
use App\Services\Dashboard\Executive\Snapshot\OperationsBlock;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Config;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Ligação determinística ao retorno do L2 `flexible` (mockado): payload **plain** (`hero` /
 * `operations` como arrays) e reidratação para DTOs no read. KPI continua ao encargo do
 * {@see DashboardMetricsService} (sem mock — classe final); por isso o teste mantém BD vazia
 * apenas com tenant utilizador criado pelo RefreshDatabase da TestCase.
 */
final class ExecutiveDashboardReadServiceCompositionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    #[Test]
    public function test_composicao_reidrata_hero_e_operations_a_partir_do_payload_plain_do_l2(): void
    {
        try {
            Config::set(['board.dashboard.snapshot_version' => 'vunit']);

            $this->seed(RolesAndPermissionsSeeder::class);

            $sharedHero = new HeroSummary(
                tasksOverdue: 7,
                votesOpen: 2,
                signaturesPending: 1,
                nextMeetingAt: null,
                nextMeetingId: null,
            );

            $sharedOps = new OperationsBlock(
                minutesPendingReview: 9,
                meetingsThisMonth: 3,
                notificationsUnread: 1,
            );

            $cache = Mockery::mock(CacheRepository::class);
            $cache->shouldReceive('has')->once()->andReturn(false);
            $cache->shouldReceive('flexible')->once()->andReturn([
                'hero' => $sharedHero->toArray(),
                'operations' => $sharedOps->toArray(),
            ]);

            $tenant = Tenant::factory()->create();
            $actor = User::factory()->create(['tenant_id' => $tenant->id]);
            $actor->assignRole('tenant_admin');

            $obs = app(ExecutiveDashboardObservability::class);

            $service = new ExecutiveDashboardReadService(
                hero: new HeroProvider,
                kpi: new KpiStripProvider(app(DashboardMetricsService::class)),
                operations: new OperationsProvider,
                priorities: new PrioritiesProvider,
                activity: new ActivityFeedProvider,
                cache: $cache,
                observability: $obs,
            );

            $snapshot = $service->read($actor, DashboardMetricsPeriod::AllTime);

            $this->assertEquals($sharedHero, $snapshot->hero);
            $this->assertEquals($sharedOps, $snapshot->operations);
            $this->assertSame('vunit', $snapshot->version);
            $this->assertIsArray($snapshot->priorities);
            $this->assertIsArray($snapshot->activity);
        } finally {
            Config::set(['board.dashboard.snapshot_version' => 'v1']);
        }
    }
}
