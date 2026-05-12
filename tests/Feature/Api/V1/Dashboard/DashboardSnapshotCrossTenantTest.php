<?php

namespace Tests\Feature\Api\V1\Dashboard;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardSnapshotCrossTenantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_snapshot_tenant_a_segmento_e_contagens_sem_dados_de_b(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        Task::factory()->count(5)->create([
            'tenant_id' => $tenantB->id,
            'status' => TaskStatus::Pending,
            'due_date' => now()->subDay(),
        ]);

        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $userA->assignRole('tenant_admin');

        $token = $userA->createToken('device', ['reports:read']);

        $res = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/dashboard/snapshot')
            ->assertOk();

        $this->assertSame('t_'.$tenantA->id, $res->json('data.cache_segment'));
        $this->assertSame(0, (int) $res->json('data.hero.tasks_overdue'));
    }

    public function test_snapshot_super_admin_cache_segment_global(): void
    {
        $tenantB = Tenant::factory()->create();
        Task::factory()->count(3)->create([
            'tenant_id' => $tenantB->id,
            'status' => TaskStatus::Pending,
            'due_date' => now()->subDay(),
        ]);

        $admin = User::factory()->create(['tenant_id' => null]);
        $admin->assignRole('super_admin');

        $token = $admin->createToken('device', ['reports:read']);

        $res = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/dashboard/snapshot')
            ->assertOk();

        $this->assertSame('global', $res->json('data.cache_segment'));
        $this->assertNotSame('t_'.$tenantB->id, $res->json('data.cache_segment'));
    }
}
