<?php

namespace Tests\Feature\Dashboard\Executive;

use App\Enums\DashboardMetricsPeriod;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheInvalidator;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheKeys;
use App\Services\Dashboard\Executive\ExecutiveDashboardReadService;
use App\Services\Reporting\ReportingContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExecutiveDashboardReadServiceTest extends TestCase
{
    private function service(): ExecutiveDashboardReadService
    {
        return app(ExecutiveDashboardReadService::class);
    }

    private function expectedSharedCacheKey(ReportingContext $ctx, DashboardMetricsPeriod $period): string
    {
        return ExecutiveDashboardCacheKeys::l2Key($ctx->cacheSegment(), $period);
    }

    private function expectedMetricsCacheKey(ReportingContext $ctx, DashboardMetricsPeriod $period): string
    {
        return ExecutiveDashboardCacheKeys::l1Key($ctx->cacheSegment(), $period);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
    }

    #[Test]
    public function test_read_devolve_snapshot_completo_com_todos_os_blocos(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $snapshot = $this->service()->read($user, DashboardMetricsPeriod::ThisMonth);
        $arr = $snapshot->toArray();

        foreach (['version', 'period', 'cache_segment', 'generated_at', 'hero', 'kpis', 'operations', 'priorities', 'activity'] as $k) {
            $this->assertArrayHasKey($k, $arr);
        }
        $this->assertSame('t_'.$tenant->id, $arr['cache_segment']);
    }

    #[Test]
    public function test_tenant_a_nao_ve_dados_do_tenant_b_e_chave_l2_e_distinta(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        Task::factory()->count(3)->create([
            'tenant_id' => $tenantB->id,
            'status' => TaskStatus::Pending,
            'due_date' => now()->subDay(),
        ]);

        $adminA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $adminA->assignRole('tenant_admin');

        $ctxA = ReportingContext::fromUser($adminA);
        $period = DashboardMetricsPeriod::AllTime;

        $this->service()->read($adminA, $period);

        $keyA = $this->expectedSharedCacheKey($ctxA, $period);
        $this->assertNotNull(Cache::get($keyA));

        $keyBExpected = sprintf(
            'dashboard_snapshot:%s:t_%s:%s:shared:plain',
            config('board.dashboard.snapshot_version'),
            $tenantB->id,
            $period->value,
        );
        $this->assertNotSame($keyA, $keyBExpected);

        $snapshotA = $this->service()->read($adminA, $period);
        $this->assertSame(0, $snapshotA->hero->tasksOverdue);
    }

    #[Test]
    public function test_cache_miss_em_cold_start_popula_chave_l2(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $ctx = ReportingContext::fromUser($user);
        $period = DashboardMetricsPeriod::Last30Days;
        $key = $this->expectedSharedCacheKey($ctx, $period);

        $this->assertNull(Cache::get($key));

        $this->service()->read($user, $period);

        $this->assertNotNull(Cache::get($key));
    }

    #[Test]
    public function test_l2_guarda_apenas_arrays_plain_para_hero_e_operations(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $ctx = ReportingContext::fromUser($user);
        $period = DashboardMetricsPeriod::Last30Days;
        $key = $this->expectedSharedCacheKey($ctx, $period);

        $this->service()->read($user, $period);

        $cached = Cache::get($key);
        $this->assertIsArray($cached);
        $this->assertArrayHasKey('hero', $cached);
        $this->assertArrayHasKey('operations', $cached);
        $this->assertIsArray($cached['hero']);
        $this->assertIsArray($cached['operations']);
        foreach (['tasks_overdue', 'votes_open', 'signatures_pending', 'next_meeting_at', 'next_meeting_id'] as $k) {
            $this->assertArrayHasKey($k, $cached['hero']);
        }
        foreach (['minutes_pending_review', 'meetings_this_month', 'notifications_unread'] as $k) {
            $this->assertArrayHasKey($k, $cached['operations']);
        }
    }

    #[Test]
    public function test_cache_hit_dentro_do_ttl_nao_incrementa_queries_hero_operations(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');
        $period = DashboardMetricsPeriod::AllTime;

        try {
            DB::enableQueryLog();
            $this->service()->read($user, $period);
            $firstCount = count(DB::getQueryLog());
            DB::flushQueryLog();

            $this->service()->read($user, $period);
            $secondCount = count(DB::getQueryLog());

            $this->assertLessThan($firstCount, $secondCount, 'Segunda leitura deve registar menos SQL (Hero/Operations vêm do L2).');
        } finally {
            DB::flushQueryLog();
            DB::disableQueryLog();
        }
    }

    #[Test]
    public function test_forget_l2_nao_invalida_l1_dashboard_metrics(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');
        $period = DashboardMetricsPeriod::ThisMonth;

        $ctx = ReportingContext::fromUser($user);
        $sharedKey = $this->expectedSharedCacheKey($ctx, $period);
        $metricsKey = $this->expectedMetricsCacheKey($ctx, $period);

        $this->service()->read($user, $period);

        $this->assertNotNull(Cache::get($metricsKey), 'L1 dashboard_metrics deveria existir após read');
        $this->assertNotNull(Cache::get($sharedKey), 'L2 shared deveria existir após read');

        Cache::forget($sharedKey);
        Cache::forget(ExecutiveDashboardCacheInvalidator::FLEXIBLE_CREATED_PREFIX.$sharedKey);

        $this->assertNull(Cache::get($sharedKey));
        $this->assertNotNull(Cache::get($metricsKey), 'forget L2 não deve limpar L1');

        $this->service()->read($user, $period);

        $this->assertNotNull(Cache::get($metricsKey));
    }

    #[Test]
    public function test_dois_users_no_mesmo_tenant_partilham_shared_mas_feeds_divergem(): void
    {
        $tenant = Tenant::factory()->create();

        $alice = User::factory()->create(['tenant_id' => $tenant->id]);
        $alice->assignRole('board_member');
        $bob = User::factory()->create(['tenant_id' => $tenant->id]);
        $bob->assignRole('board_member');

        Task::factory()->create([
            'tenant_id' => $tenant->id,
            'assigned_to' => $alice->id,
            'status' => TaskStatus::Pending,
            'due_date' => now()->addDay(),
        ]);

        $period = DashboardMetricsPeriod::AllTime;

        $snapAlice = $this->service()->read($alice, $period);
        $snapBob = $this->service()->read($bob, $period);

        $this->assertEquals($snapAlice->hero->toArray(), $snapBob->hero->toArray());
        $this->assertEquals($snapAlice->operations->toArray(), $snapBob->operations->toArray());
        $this->assertGreaterThanOrEqual(1, count($snapAlice->priorities));
        $this->assertCount(0, $snapBob->priorities);
    }

    #[Test]
    public function test_super_admin_global_usa_chave_global_e_feeds_vazios(): void
    {
        $tenant = Tenant::factory()->create();
        Task::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => TaskStatus::Pending,
            'due_date' => now()->subDay(),
        ]);

        $global = User::factory()->create([
            'tenant_id' => null,
            'is_super_admin' => true,
        ]);

        $period = DashboardMetricsPeriod::AllTime;
        $ctx = ReportingContext::fromUser($global);
        $this->assertSame('global', $ctx->cacheSegment());

        $snapshot = $this->service()->read($global, $period);

        $this->assertSame('global', $snapshot->cacheSegment);
        $this->assertSame([], $snapshot->priorities);
        $this->assertSame([], $snapshot->activity);
        $this->assertGreaterThanOrEqual(1, $snapshot->hero->tasksOverdue);
    }

    #[Test]
    public function test_snapshot_vazio_para_tenant_sem_dados_mantem_shape(): void
    {
        $tenant = Tenant::factory()->create();
        // board_member: sem viewAny sobre audit_logs ⇒ activity []; tenant sem dados ⇒ hero/operations/priorities vazios.
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('board_member');

        $period = DashboardMetricsPeriod::ThisMonth;
        $snap = $this->service()->read($user, $period);

        // KPI vem sempre de DashboardMetricsService (L1 próprio): zeros com chaves estáveis ≠ emptyShape com arrays vazios.
        $this->assertSame(0, $snap->hero->tasksOverdue);
        $this->assertSame(0, $snap->operations->notificationsUnread);
        $this->assertSame([], $snap->priorities);
        $this->assertSame([], $snap->activity);
        $this->assertNotSame([], $snap->kpis->tasks);
    }

    #[Test]
    public function test_periodo_diferente_usa_chave_l2_distinta(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $ctx = ReportingContext::fromUser($user);
        $k1 = $this->expectedSharedCacheKey($ctx, DashboardMetricsPeriod::ThisMonth);
        $k2 = $this->expectedSharedCacheKey($ctx, DashboardMetricsPeriod::AllTime);

        $this->assertNotSame($k1, $k2);
    }

    #[Test]
    public function test_bump_de_snapshot_version_produz_chave_l2_nova(): void
    {
        config(['board.dashboard.snapshot_version' => 'v1']);
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');
        $ctx = ReportingContext::fromUser($user);
        $period = DashboardMetricsPeriod::ThisMonth;

        $kV1 = $this->expectedSharedCacheKey($ctx, $period);

        try {
            config(['board.dashboard.snapshot_version' => 'v2']);
            $kV2 = $this->expectedSharedCacheKey($ctx, $period);

            $this->assertStringContainsString('dashboard_snapshot:v1:', $kV1);
            $this->assertStringContainsString('dashboard_snapshot:v2:', $kV2);
        } finally {
            config(['board.dashboard.snapshot_version' => 'v1']);
        }
    }
}
