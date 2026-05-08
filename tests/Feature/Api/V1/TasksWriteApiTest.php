<?php

namespace Tests\Feature\Api\V1;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TasksWriteApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_post_api_v1_tasks_cria_task_pending_default(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $token = $admin->createToken('device', ['tasks:write']);

        $res = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/tasks', [
                'title' => 'Nova task',
                'priority' => TaskPriority::Normal->value,
            ])
            ->assertStatus(201);

        $id = (int) ($res->json('data.id') ?? 0);
        $this->assertGreaterThan(0, $id);

        $task = Task::query()->withoutGlobalScopes()->findOrFail($id);
        $this->assertSame($tenant->id, $task->tenant_id);
        $this->assertSame(TaskStatus::Pending, $task->status);
    }

    public function test_post_rejeita_tenant_id_status_id_completed_at_created_by(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $token = $admin->createToken('device', ['tasks:write']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/tasks', [
                'tenant_id' => $tenant->id,
                'status' => TaskStatus::Completed->value,
                'id' => 999,
                'completed_at' => now()->toISOString(),
                'created_by' => 1,
                'title' => 'X',
                'priority' => TaskPriority::Normal->value,
            ])
            ->assertStatus(422);
    }

    public function test_post_rejeita_assigned_to_cross_tenant(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();

        $admin = User::factory()->create(['tenant_id' => $tA->id]);
        $admin->assignRole('tenant_admin');
        $foreign = User::factory()->create(['tenant_id' => $tB->id]);

        $token = $admin->createToken('device', ['tasks:write']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/tasks', [
                'title' => 'X',
                'priority' => TaskPriority::Normal->value,
                'assigned_to' => $foreign->id,
            ])
            ->assertStatus(422);
    }

    public function test_patch_funciona_rejeita_campos_proibidos_e_nao_altera_status(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $task = Task::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => TaskStatus::Pending,
            'title' => 'Antes',
        ]);

        $token = $admin->createToken('device', ['tasks:write']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->patchJson('/api/v1/tasks/'.$task->id, ['title' => 'Depois'])
            ->assertOk();

        $task->refresh();
        $this->assertSame(TaskStatus::Pending, $task->status);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->patchJson('/api/v1/tasks/'.$task->id, [
                'tenant_id' => 1,
                'status' => TaskStatus::Completed->value,
                'completed_at' => now()->toISOString(),
                'created_by' => 1,
                'id' => 9,
                'title' => 'X',
            ])
            ->assertStatus(422);
    }

    public function test_patch_em_task_invisivel_retorna_404_generico(): void
    {
        $tenant = Tenant::factory()->create();

        $u1 = User::factory()->create(['tenant_id' => $tenant->id]);
        $u1->assignRole('board_member');
        $u2 = User::factory()->create(['tenant_id' => $tenant->id]);

        $task = Task::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $u2->id]);

        $token = $u1->createToken('device', ['tasks:write']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->patchJson('/api/v1/tasks/'.$task->id, ['title' => 'X'])
            ->assertStatus(404);
    }

    public function test_transicoes_start_complete_cancel_e_transicao_invalida_retorna_422(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $token = $admin->createToken('device', ['tasks:write']);

        $task = Task::factory()->create(['tenant_id' => $tenant->id, 'status' => TaskStatus::Pending]);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/tasks/'.$task->id.'/start')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/tasks/'.$task->id.'/complete')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/tasks/'.$task->id.'/cancel')
            ->assertStatus(422);
    }

    public function test_comments_valido_vazio_maior_que_5000_e_invisivel_404(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $token = $admin->createToken('device', ['tasks:write']);

        $task = Task::factory()->create(['tenant_id' => $tenant->id]);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/tasks/'.$task->id.'/comments', ['comment' => 'Olá'])
            ->assertStatus(201);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/tasks/'.$task->id.'/comments', ['comment' => ''])
            ->assertStatus(422);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/tasks/'.$task->id.'/comments', ['comment' => str_repeat('a', 5001)])
            ->assertStatus(422);

        $tenantB = Tenant::factory()->create();
        $u1 = User::factory()->create(['tenant_id' => $tenant->id]);
        $u1->assignRole('board_member');
        $invisible = Task::factory()->create(['tenant_id' => $tenantB->id]);

        $token2 = $u1->createToken('device', ['tasks:write']);

        $this->withHeader('Authorization', 'Bearer '.$token2->plainTextToken)
            ->postJson('/api/v1/tasks/'.$invisible->id.'/comments', ['comment' => 'x'])
            ->assertStatus(404);
    }

    public function test_rate_limit_comments_retorna_429(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $token = $admin->createToken('device', ['tasks:write']);

        $task = Task::factory()->create(['tenant_id' => $tenant->id]);

        for ($i = 0; $i < 20; $i++) {
            $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
                ->postJson('/api/v1/tasks/'.$task->id.'/comments', ['comment' => 'x'])
                ->assertStatus(201);
        }

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/tasks/'.$task->id.'/comments', ['comment' => 'x'])
            ->assertStatus(429);
    }

    public function test_super_admin_sem_tenant_id_nao_consegue_criar_task_via_api(): void
    {
        $super = User::factory()->create(['tenant_id' => null, 'is_super_admin' => true]);
        $super->assignRole('super_admin');

        $token = $super->createToken('device', ['tasks:write']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/tasks', [
                'title' => 'Global',
                'priority' => TaskPriority::Urgent->value,
            ])
            ->assertStatus(422);
    }
}

