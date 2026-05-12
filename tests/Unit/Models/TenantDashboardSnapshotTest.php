<?php

namespace Tests\Unit\Models;

use App\Models\Tenant;
use App\Models\TenantDashboardSnapshot;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TenantDashboardSnapshotTest extends TestCase
{
    #[Test]
    public function test_tenant_scope_isola_leitura_entre_tenants(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();
        $uA = User::factory()->create(['tenant_id' => $tA->id]);
        $uA->assignRole('tenant_admin');

        $rowB = TenantDashboardSnapshot::factory()->create([
            'tenant_id' => $tB->id,
            'period' => 'this_month',
        ]);

        $this->actingAs($uA);

        $this->assertNull(TenantDashboardSnapshot::query()->find($rowB->id));
        $found = TenantDashboardSnapshot::query()->withoutGlobalScopes()->find($rowB->id);
        $this->assertNotNull($found);
        $this->assertSame((int) $tB->id, (int) $found->tenant_id);
    }

    #[Test]
    public function test_scope_valid_exclui_stale_e_refreshed_antigo(): void
    {
        Carbon::setTestNow('2026-08-01 12:00:00');

        $tenant = Tenant::factory()->create();
        $fresh = TenantDashboardSnapshot::factory()->create([
            'tenant_id' => $tenant->id,
            'is_stale' => false,
            'refreshed_at' => now()->subMinutes(5),
        ]);
        $staleFlag = TenantDashboardSnapshot::factory()->create([
            'tenant_id' => $tenant->id,
            'period' => 'last_30_days',
            'is_stale' => true,
            'refreshed_at' => now()->subMinutes(1),
        ]);
        $tooOld = TenantDashboardSnapshot::factory()->create([
            'tenant_id' => $tenant->id,
            'period' => 'all_time',
            'is_stale' => false,
            'refreshed_at' => now()->subMinutes(11),
        ]);

        $ids = TenantDashboardSnapshot::query()->withoutGlobalScopes()->valid()->pluck('id')->all();
        $this->assertContains($fresh->id, $ids);
        $this->assertNotContains($staleFlag->id, $ids);
        $this->assertNotContains($tooOld->id, $ids);

        Carbon::setTestNow();
    }
}
