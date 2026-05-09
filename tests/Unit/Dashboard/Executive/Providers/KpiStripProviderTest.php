<?php

namespace Tests\Unit\Dashboard\Executive\Providers;

use App\Enums\DashboardMetricsPeriod;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Dashboard\DashboardMetricsService;
use App\Services\Dashboard\Executive\Providers\KpiStripProvider;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class KpiStripProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * {@see DashboardMetricsService} é `final`; não há mock PHPUnit. Validamos delegação pelo alinhamento
     * estrito com {@see DashboardMetricsService::getUncachedMetrics} (primeira leitura pós-cache flush).
     */
    #[Test]
    public function test_strip_reflecta_get_uncached_sem_reinterpretar_arrays(): void
    {
        Cache::flush();

        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        $service = app(DashboardMetricsService::class);
        $period = DashboardMetricsPeriod::Last30Days;
        $uncached = $service->getUncachedMetrics($actor, $period);

        $strip = (new KpiStripProvider($service))->build($actor, $period);

        $this->assertSame($uncached['tasks'], $strip->tasks);
        $this->assertSame($uncached['meetings'], $strip->meetings);
        $this->assertSame($uncached['votes'], $strip->votes);
        $this->assertSame($uncached['signatures'], $strip->signatures);
    }
}
