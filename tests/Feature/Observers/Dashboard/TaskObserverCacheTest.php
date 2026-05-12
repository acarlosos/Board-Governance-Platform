<?php

namespace Tests\Feature\Observers\Dashboard;

use App\Enums\DashboardMetricsPeriod;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Tenant;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheKeys;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TaskObserverCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
    }

    #[Test]
    public function test_created_task_invalida_chave_l1_sentinela(): void
    {
        $tenant = Tenant::factory()->create();
        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);
        Cache::put($key, 'X', 600);

        Task::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => TaskStatus::Pending,
        ]);

        $this->assertNull(Cache::get($key));
    }

    #[Test]
    public function test_bulk_update_title_sem_observer_nao_limpa_cache_repopulada(): void
    {
        $tenant = Tenant::factory()->create();
        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);

        $task = Task::factory()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Original',
            'status' => TaskStatus::Pending,
        ]);

        Cache::put($key, 'REPOP', 600);

        Task::query()->whereKey($task->id)->update(['title' => 'x']);

        $this->assertSame('REPOP', Cache::get($key));
    }

    #[Test]
    public function test_update_status_kpi_invalida_cache(): void
    {
        $tenant = Tenant::factory()->create();
        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);

        $task = Task::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => TaskStatus::Pending,
        ]);

        Cache::put($key, 'WARM', 600);

        $task->update(['status' => TaskStatus::Completed]);

        $this->assertNull(Cache::get($key));
    }
}
