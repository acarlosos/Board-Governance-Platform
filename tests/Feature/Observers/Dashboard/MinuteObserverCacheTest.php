<?php

namespace Tests\Feature\Observers\Dashboard;

use App\Enums\DashboardMetricsPeriod;
use App\Enums\MinuteStatus;
use App\Models\Minute;
use App\Models\Tenant;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheKeys;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MinuteObserverCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
    }

    #[Test]
    public function test_created_minute_invalida_chave_l1_sentinela(): void
    {
        $tenant = Tenant::factory()->create();
        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);
        Cache::put($key, 'X', 600);

        Minute::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => MinuteStatus::Draft,
        ]);

        $this->assertNull(Cache::get($key));
    }

    #[Test]
    public function test_bulk_update_title_sem_observer_preserva_cache_repopulada(): void
    {
        $tenant = Tenant::factory()->create();
        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);

        $minute = Minute::factory()->create([
            'tenant_id' => $tenant->id,
            'title' => 'M1',
        ]);

        Cache::put($key, 'REPOP', 600);

        Minute::query()->whereKey($minute->id)->update(['title' => 'M2']);

        $this->assertSame('REPOP', Cache::get($key));
    }

    #[Test]
    public function test_update_status_kpi_invalida_cache(): void
    {
        $tenant = Tenant::factory()->create();
        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);

        $minute = Minute::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => MinuteStatus::Draft,
        ]);

        Cache::put($key, 'WARM', 600);

        $minute->update(['status' => MinuteStatus::InReview]);

        $this->assertNull(Cache::get($key));
    }
}
