<?php

namespace Tests\Feature\Api\V1;

use App\Models\Board;
use App\Models\Meeting;
use App\Models\NotificationCenter;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

final class DomainReadOnlyApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_domain_endpoints_exigem_auth(): void
    {
        $this->getJson('/api/v1/boards')->assertStatus(401);
        $this->getJson('/api/v1/meetings')->assertStatus(401);
        $this->getJson('/api/v1/tasks')->assertStatus(401);
        $this->getJson('/api/v1/notifications')->assertStatus(401);
    }

    public function test_domain_endpoints_exigem_ability(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $token = $user->createToken('device', ['auth:read']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/boards')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_tasks_self_service_lista_apenas_atribuidas(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('board_member');

        $mine = Task::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $user->id]);
        $other = Task::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => User::factory()->create(['tenant_id' => $tenant->id])->id]);

        $token = $user->createToken('device', ['tasks:read']);

        $res = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/tasks?per_page=100')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.api_version', 'v1');

        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_tasks_tenant_admin_lista_tasks_do_tenant_e_pode_filtrar_assigned_to_me(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        Task::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $admin->id]);
        Task::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => User::factory()->create(['tenant_id' => $tenant->id])->id]);

        $token = $admin->createToken('device', ['tasks:read']);

        $res = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/tasks?assigned_to=me&per_page=100')
            ->assertOk();

        $this->assertCount(1, $res->json('data'));
    }

    public function test_notifications_self_service_lista_apenas_proprias(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('board_member');

        $mine = NotificationCenter::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id]);
        $other = NotificationCenter::factory()->create(['tenant_id' => $tenant->id, 'user_id' => User::factory()->create(['tenant_id' => $tenant->id])->id]);

        $token = $user->createToken('device', ['notifications:read']);

        $res = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/notifications?per_page=100')
            ->assertOk();

        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_show_invisivel_retorna_404_generico(): void
    {
        $t = Tenant::factory()->create();
        $u1 = User::factory()->create(['tenant_id' => $t->id]);
        $u1->assignRole('board_member');

        $u2 = User::factory()->create(['tenant_id' => $t->id]);
        $task = Task::factory()->create(['tenant_id' => $t->id, 'assigned_to' => $u2->id]);

        $token = $u1->createToken('device', ['tasks:read']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/tasks/'.$task->id)
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'not_found');
    }

    public function test_isolamento_cross_tenant_nao_vaza(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();

        $userA = User::factory()->create(['tenant_id' => $tA->id]);
        $userA->assignRole('tenant_admin');

        $boardB = Board::factory()->create(['tenant_id' => $tB->id]);

        $token = $userA->createToken('device', ['boards:read']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/boards/'.$boardB->id)
            ->assertStatus(404);
    }

    public function test_swagger_ui_disponivel(): void
    {
        $this->get('/docs/api')
            ->assertOk()
            ->assertSee('swagger-ui', false);
    }
}

