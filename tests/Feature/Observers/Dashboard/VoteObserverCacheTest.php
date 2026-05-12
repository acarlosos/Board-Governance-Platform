<?php

namespace Tests\Feature\Observers\Dashboard;

use App\Enums\DashboardMetricsPeriod;
use App\Enums\VoteStatus;
use App\Models\Tenant;
use App\Models\Vote;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheKeys;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class VoteObserverCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
    }

    #[Test]
    public function test_created_vote_invalida_chave_l1_sentinela(): void
    {
        $tenant = Tenant::factory()->create();
        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);
        Cache::put($key, 'X', 600);

        Vote::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => VoteStatus::Draft,
        ]);

        $this->assertNull(Cache::get($key));
    }

    #[Test]
    public function test_bulk_update_title_sem_observer_preserva_cache_repopulada(): void
    {
        $tenant = Tenant::factory()->create();
        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);

        $vote = Vote::factory()->create([
            'tenant_id' => $tenant->id,
            'title' => 'V1',
        ]);

        Cache::put($key, 'REPOP', 600);

        Vote::query()->whereKey($vote->id)->update(['title' => 'V2']);

        $this->assertSame('REPOP', Cache::get($key));
    }

    #[Test]
    public function test_update_status_kpi_invalida_cache(): void
    {
        $tenant = Tenant::factory()->create();
        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);

        $vote = Vote::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => VoteStatus::Draft,
        ]);

        Cache::put($key, 'WARM', 600);

        $vote->update(['status' => VoteStatus::Open]);

        $this->assertNull(Cache::get($key));
    }
}
