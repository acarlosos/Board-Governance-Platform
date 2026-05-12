<?php

namespace Tests\Feature\Observers\Dashboard;

use App\Enums\DashboardMetricsPeriod;
use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheKeys;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MeetingObserverCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
    }

    #[Test]
    public function test_created_meeting_invalida_chave_l1_sentinela(): void
    {
        $tenant = Tenant::factory()->create();
        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);
        Cache::put($key, 'X', 600);

        Meeting::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertNull(Cache::get($key));
    }

    #[Test]
    public function test_bulk_update_title_sem_observer_preserva_cache_repopulada(): void
    {
        $tenant = Tenant::factory()->create();
        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);

        $meeting = Meeting::factory()->create([
            'tenant_id' => $tenant->id,
            'title' => 'T1',
        ]);

        Cache::put($key, 'REPOP', 600);

        Meeting::query()->whereKey($meeting->id)->update(['title' => 'T2']);

        $this->assertSame('REPOP', Cache::get($key));
    }

    #[Test]
    public function test_update_status_kpi_invalida_cache(): void
    {
        $tenant = Tenant::factory()->create();
        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);

        $meeting = Meeting::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => MeetingStatus::Draft,
        ]);

        Cache::put($key, 'WARM', 600);

        $meeting->update(['status' => MeetingStatus::Scheduled]);

        $this->assertNull(Cache::get($key));
    }
}
