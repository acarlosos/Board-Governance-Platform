<?php

namespace Tests\Feature\Observers\Dashboard;

use App\Enums\DashboardMetricsPeriod;
use App\Enums\NotificationStatus;
use App\Models\NotificationCenter;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheKeys;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class NotificationCenterObserverCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
    }

    #[Test]
    public function test_created_notification_invalida_chave_l1_sentinela(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);
        Cache::put($key, 'X', 600);

        NotificationCenter::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => NotificationStatus::Unread,
        ]);

        $this->assertNull(Cache::get($key));
    }

    #[Test]
    public function test_bulk_update_body_sem_observer_preserva_cache_repopulada(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $n = NotificationCenter::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => NotificationStatus::Unread,
        ]);

        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);
        Cache::put($key, 'REPOP', 600);

        NotificationCenter::query()->whereKey($n->id)->update(['body' => 'outro corpo']);

        $this->assertSame('REPOP', Cache::get($key));
    }

    #[Test]
    public function test_update_read_at_kpi_invalida_cache(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $n = NotificationCenter::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => NotificationStatus::Unread,
            'read_at' => null,
        ]);

        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);
        Cache::put($key, 'WARM', 600);

        $n->update(['read_at' => now()]);

        $this->assertNull(Cache::get($key));
    }
}
