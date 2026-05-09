<?php

namespace Tests\Unit\Dashboard\Executive\Providers;

use App\Enums\DashboardMetricsPeriod;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Dashboard\Executive\Providers\PrioritiesProvider;
use Database\Seeders\RolesAndPermissionsSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PrioritiesProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function test_d4_super_admin_global_entrega_feed_vazio(): void
    {
        $tenant = Tenant::factory()->create();
        Task::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => TaskStatus::Pending,
            'assigned_to' => User::factory()->create(['tenant_id' => $tenant->id])->id,
        ]);

        $global = User::factory()->create([
            'tenant_id' => null,
            'is_super_admin' => true,
        ]);

        $items = (new PrioritiesProvider)->build($global, DashboardMetricsPeriod::AllTime);

        $this->assertSame([], $items);
    }

    #[Test]
    public function test_d9_board_member_ve_so_tasks_atribuidas_a_si_no_mesmo_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('board_member');

        $peer = User::factory()->create(['tenant_id' => $tenant->id]);

        Task::factory()->count(5)->create([
            'tenant_id' => $tenant->id,
            'status' => TaskStatus::Pending,
            'assigned_to' => $actor->id,
            'due_date' => now()->addDay(),
        ]);
        Task::factory()->count(5)->create([
            'tenant_id' => $tenant->id,
            'status' => TaskStatus::Pending,
            'assigned_to' => $peer->id,
            'due_date' => now()->addDay(),
        ]);

        $items = (new PrioritiesProvider)->build($actor, DashboardMetricsPeriod::AllTime);

        $this->assertCount(5, $items);
        foreach ($items as $item) {
            $this->assertSame('task', $item->resourceType);
            $tid = Task::query()->withoutGlobalScopes()->findOrFail($item->id)->assigned_to;
            $this->assertSame((int) $actor->id, (int) $tid);
        }
    }

    #[Test]
    public function test_take_respeita_priorities_max_para_tasks_visiveis(): void
    {
        $tenant = Tenant::factory()->create();

        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('board_member');

        Task::factory()->count(12)->create([
            'tenant_id' => $tenant->id,
            'status' => TaskStatus::InProgress,
            'assigned_to' => $actor->id,
            'due_date' => now()->addDay(),
        ]);

        $items = (new PrioritiesProvider)->build($actor, DashboardMetricsPeriod::AllTime);

        $this->assertCount(10, $items);
    }

    #[Test]
    public function test_payload_priorities_nao_inclui_contagem_de_itens_descartados(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('board_member');

        Task::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'assigned_to' => $actor->id,
            'status' => TaskStatus::Pending,
        ]);

        $items = (new PrioritiesProvider)->build($actor, DashboardMetricsPeriod::AllTime);
        $json = json_encode(array_map(static fn ($i) => $i->toArray(), $items), JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('omit', strtolower($json));
        $this->assertStringNotContainsString('discard', strtolower($json));
        $this->assertStringNotContainsString('candidate', strtolower($json));
        $this->assertStringNotContainsString('hidden', strtolower($json));

        foreach ($items as $item) {
            foreach (array_keys($item->toArray()) as $key) {
                $this->assertStringNotContainsString('omit', strtolower($key));
                $this->assertStringNotContainsString('hidden', strtolower($key));
            }
        }
    }

    #[Test]
    public function test_tenant_b_tasks_nunca_aparecem_para_utilizador_do_tenant_a(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $actor = User::factory()->create(['tenant_id' => $tenantA->id]);
        $actor->assignRole('tenant_admin');

        Task::factory()->create([
            'tenant_id' => $tenantB->id,
            'status' => TaskStatus::Pending,
            'assigned_to' => User::factory()->create(['tenant_id' => $tenantB->id])->id,
        ]);

        Task::factory()->create([
            'tenant_id' => $tenantA->id,
            'status' => TaskStatus::Pending,
            'assigned_to' => $actor->id,
            'due_date' => now()->addDay(),
        ]);

        $items = (new PrioritiesProvider)->build($actor, DashboardMetricsPeriod::AllTime);

        foreach ($items as $item) {
            if ($item->resourceType === 'task') {
                $tid = Task::query()->withoutGlobalScopes()->findOrFail($item->id)->tenant_id;
                $this->assertSame((int) $tenantA->id, (int) $tid);
            }
        }
    }

    #[Test]
    public function test_tenant_admin_ve_tasks_de_outros_utilizadores(): void
    {
        $tenant = Tenant::factory()->create();

        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        Task::factory()->count(12)->create([
            'tenant_id' => $tenant->id,
            'status' => TaskStatus::Pending,
            'assigned_to' => $owner->id,
            'due_date' => now()->addDay(),
        ]);

        $items = (new PrioritiesProvider)->build($admin, DashboardMetricsPeriod::AllTime);

        $this->assertCount(10, $items);
        $this->assertGreaterThanOrEqual(10, count(array_filter(
            $items,
            static fn ($i) => $i->resourceType === 'task',
        )));
    }
}
