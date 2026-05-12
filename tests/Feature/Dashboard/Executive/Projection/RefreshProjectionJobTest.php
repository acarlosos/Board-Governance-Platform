<?php

namespace Tests\Feature\Dashboard\Executive\Projection;

use App\Enums\DashboardMetricsPeriod;
use App\Jobs\Dashboard\RefreshTenantDashboardSnapshotJob;
use App\Models\Tenant;
use App\Models\TenantDashboardSnapshot;
use App\Models\User;
use App\Services\Dashboard\Executive\Projection\DashboardProjectionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RefreshProjectionJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_handle_executa_refresh_for_via_servico_real(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $job = new RefreshTenantDashboardSnapshotJob((int) $tenant->id, DashboardMetricsPeriod::ThisMonth);
        $job->handle(app(DashboardProjectionService::class));

        $row = TenantDashboardSnapshot::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('period', DashboardMetricsPeriod::ThisMonth->value)
            ->first();

        $this->assertNotNull($row);
        $this->assertFalse($row->is_stale);
        $this->assertIsArray($row->payload);
        $this->assertArrayHasKey('hero', $row->payload);
        $this->assertArrayHasKey('operations', $row->payload);
    }
}
