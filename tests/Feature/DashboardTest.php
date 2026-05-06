<?php

namespace Tests\Feature;

use App\Enums\DashboardMetricsPeriod;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Dashboard\DashboardMetricsService;
use App\Services\Reporting\ReportingContext;
use App\Services\Reports\ReportsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function dashboardService(): DashboardMetricsService
    {
        return app(DashboardMetricsService::class);
    }

    public function test_tenant_admin_metrics_nao_contam_tasks_de_outro_tenant(): void
    {
        Cache::flush();
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        Task::factory()->count(4)->create([
            'tenant_id' => $tenantA->id,
            'status' => TaskStatus::Pending,
        ]);
        Task::factory()->count(11)->create([
            'tenant_id' => $tenantB->id,
            'status' => TaskStatus::Completed,
        ]);

        $admin = User::factory()->create(['tenant_id' => $tenantA->id]);
        $admin->assignRole('tenant_admin');

        $m = $this->dashboardService()->getUncachedMetrics($admin, DashboardMetricsPeriod::AllTime);

        $this->assertSame(4, $m['tasks']['total_tasks']);

        $ctx = ReportingContext::fromUser($admin);
        $this->assertFalse($ctx->isGlobalScope());
        $this->assertSame((int) $tenantA->id, $ctx->tenantId());
    }

    public function test_reports_service_tasks_by_status_respeita_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        Task::factory()->create(['tenant_id' => $tenantA->id, 'status' => TaskStatus::Pending]);
        Task::factory()->create(['tenant_id' => $tenantB->id, 'status' => TaskStatus::Cancelled]);

        $admin = User::factory()->create(['tenant_id' => $tenantA->id]);
        $admin->assignRole('tenant_admin');

        $by = app(ReportsService::class)->tasksByStatus($admin, DashboardMetricsPeriod::AllTime);

        $this->assertArrayHasKey('pending', $by);
        $this->assertSame(1, $by['pending']);
        $this->assertArrayNotHasKey('cancelled', $by);
    }

    public function test_super_admin_pode_visualizar_metricas_agregadas_globais(): void
    {
        Cache::flush();
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        Task::factory()->count(2)->create(['tenant_id' => $tenantA->id]);
        Task::factory()->count(8)->create(['tenant_id' => $tenantB->id]);

        $super = User::factory()->create([
            'tenant_id' => null,
            'is_super_admin' => false,
        ]);
        $super->assignRole('super_admin');

        $ctx = ReportingContext::fromUser($super);
        $this->assertTrue($ctx->isGlobalScope());

        $m = $this->dashboardService()->getUncachedMetrics($super, DashboardMetricsPeriod::AllTime);

        $this->assertSame(10, $m['tasks']['total_tasks']);
    }

    public function test_dashboard_cache_servico_retorna_mesmo_total_antes_do_ttl_mesmo_com_novos_registos(): void
    {
        Cache::flush();

        $tenant = Tenant::factory()->create();

        Task::factory()->create(['tenant_id' => $tenant->id]);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $service = $this->dashboardService();
        $first = $service->getMetrics($admin, DashboardMetricsPeriod::AllTime);

        Task::factory()->count(10)->create(['tenant_id' => $tenant->id]);

        $second = $service->getMetrics($admin, DashboardMetricsPeriod::AllTime);

        $this->assertSame($first['tasks']['total_tasks'], $second['tasks']['total_tasks']);

        Cache::forget(sprintf(
            '%s:%s:%s',
            'dashboard_metrics:v1',
            't_'.$tenant->id,
            DashboardMetricsPeriod::AllTime->value
        ));

        $afterForget = $service->getMetrics($admin, DashboardMetricsPeriod::AllTime);

        $this->assertSame(11, $afterForget['tasks']['total_tasks']);
    }

    public function test_tenant_admin_nunca_tem_contexto_global(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $ctx = ReportingContext::fromUser($admin);

        $this->assertFalse($ctx->isGlobalScope());
        $this->assertSame((int) $tenant->id, $ctx->tenantId());
    }
}
