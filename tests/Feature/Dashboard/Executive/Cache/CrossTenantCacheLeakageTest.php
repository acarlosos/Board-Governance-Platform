<?php

namespace Tests\Feature\Dashboard\Executive\Cache;

use App\Enums\DashboardMetricsPeriod;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Dashboard\DashboardMetricsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CrossTenantCacheLeakageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
    }

    #[Test]
    public function test_mutacao_tenant_a_nao_forca_recalculo_de_metricas_do_tenant_b(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $userA->assignRole('tenant_admin');
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);
        $userB->assignRole('tenant_admin');

        $svc = app(DashboardMetricsService::class);
        $period = DashboardMetricsPeriod::ThisMonth;

        $svc->getMetrics($userA, $period);
        $svc->getMetrics($userB, $period);
        $svc->getMetrics($userA, $period);
        $svc->getMetrics($userB, $period);

        Task::factory()->create([
            'tenant_id' => $tenantA->id,
            'status' => TaskStatus::Pending,
        ]);

        try {
            DB::enableQueryLog();
            $svc->getMetrics($userA, $period);
            $queriesA = count(DB::getQueryLog());
            DB::flushQueryLog();

            $svc->getMetrics($userB, $period);
            $queriesB = count(DB::getQueryLog());

            $this->assertGreaterThan(0, $queriesA, 'Tenant A deve recalcular após mutação.');
            $this->assertLessThan($queriesA, $queriesB, 'Tenant B deve manter L1 quente com menos SQL que A.');
        } finally {
            DB::flushQueryLog();
            DB::disableQueryLog();
        }
    }
}
