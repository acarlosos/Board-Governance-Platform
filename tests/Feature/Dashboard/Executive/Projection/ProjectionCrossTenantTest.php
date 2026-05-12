<?php

namespace Tests\Feature\Dashboard\Executive\Projection;

use App\Models\Task;
use App\Models\Tenant;
use App\Models\TenantDashboardSnapshot;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectionCrossTenantTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_task_em_tenant_a_marca_stale_somente_projection_de_a(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantDashboardSnapshot::factory()->create([
            'tenant_id' => $tenantA->id,
            'period' => 'this_month',
            'is_stale' => false,
        ]);
        TenantDashboardSnapshot::factory()->create([
            'tenant_id' => $tenantB->id,
            'period' => 'this_month',
            'is_stale' => false,
        ]);

        Task::factory()->create(['tenant_id' => $tenantA->id]);

        $this->assertTrue((bool) TenantDashboardSnapshot::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenantA->id)->value('is_stale'));
        $this->assertFalse((bool) TenantDashboardSnapshot::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenantB->id)->value('is_stale'));

        $this->assertSame(
            0,
            TenantDashboardSnapshot::query()->withoutGlobalScopes()
                ->where('tenant_id', $tenantB->id)
                ->where('is_stale', true)
                ->count()
        );
    }
}
