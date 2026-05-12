<?php

namespace Tests\Feature\Dashboard\Executive\Projection;

use App\Enums\DashboardMetricsPeriod;
use App\Jobs\Dashboard\RefreshTenantDashboardSnapshotJob;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RefreshProjectionsCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_artisan_refresh_projections_com_tenant_dispatcha_tres_jobs(): void
    {
        Bus::fake();

        $tenant = Tenant::factory()->create();

        $this->artisan('dashboard:refresh-projections', [
            '--tenant' => (string) $tenant->id,
            '--force' => true,
        ])->assertSuccessful();

        Bus::assertDispatchedTimes(RefreshTenantDashboardSnapshotJob::class, 3);

        $periods = [];
        Bus::assertDispatched(RefreshTenantDashboardSnapshotJob::class, function (RefreshTenantDashboardSnapshotJob $job) use (&$periods, $tenant): bool {
            $this->assertSame((int) $tenant->id, $job->tenantId);
            $periods[] = $job->period;

            return true;
        });

        $values = array_map(static fn (DashboardMetricsPeriod $p): string => $p->value, $periods);
        sort($values);
        $this->assertSame(['all_time', 'last_30_days', 'this_month'], $values);
    }
}
