<?php

namespace Tests\Feature\Dashboard\Executive\Projection;

use App\Enums\DashboardMetricsPeriod;
use App\Models\Tenant;
use App\Models\TenantDashboardSnapshot;
use App\Models\User;
use App\Services\Dashboard\Executive\Projection\DashboardProjectionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DashboardProjectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    #[Test]
    public function test_refresh_for_creates_row_e_segunda_chamada_faz_update(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $svc = app(DashboardProjectionService::class);
        $svc->refreshFor((int) $tenant->id, DashboardMetricsPeriod::ThisMonth);

        $this->assertSame(1, TenantDashboardSnapshot::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
        $row1 = TenantDashboardSnapshot::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($row1);
        $this->assertFalse($row1->is_stale);
        $firstTs = $row1->refreshed_at?->timestamp;

        $this->travel(2)->seconds();
        $svc->refreshFor((int) $tenant->id, DashboardMetricsPeriod::ThisMonth);

        $this->assertSame(1, TenantDashboardSnapshot::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
        $row2 = TenantDashboardSnapshot::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($row2);
        $this->assertNotSame($firstTs, $row2->refreshed_at?->timestamp);
    }

    #[Test]
    public function test_refresh_for_grava_payload_version_igual_ao_config(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Config::set(['board.dashboard.snapshot_version' => 'vtest99']);

        try {
            $tenant = Tenant::factory()->create();
            $user = User::factory()->create(['tenant_id' => $tenant->id]);
            $user->assignRole('tenant_admin');

            app(DashboardProjectionService::class)->refreshFor((int) $tenant->id, DashboardMetricsPeriod::AllTime);

            $payload = TenantDashboardSnapshot::query()->withoutGlobalScopes()->first()?->payload;
            $this->assertIsArray($payload);
            $this->assertSame('vtest99', $payload['version'] ?? null);
        } finally {
            Config::set(['board.dashboard.snapshot_version' => 'v1']);
        }
    }

    #[Test]
    public function test_mark_stale_actualiza_somente_linhas_do_tenant(): void
    {
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

        app(DashboardProjectionService::class)->markStale((int) $tenantA->id);

        $this->assertTrue((bool) TenantDashboardSnapshot::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenantA->id)->value('is_stale'));
        $this->assertFalse((bool) TenantDashboardSnapshot::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenantB->id)->value('is_stale'));
    }
}
