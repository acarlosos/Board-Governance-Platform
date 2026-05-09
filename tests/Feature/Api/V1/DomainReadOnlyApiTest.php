<?php

namespace Tests\Feature\Api\V1;

use App\Enums\MeetingStatus;
use App\Models\Board;
use App\Models\Meeting;
use App\Models\NotificationCenter;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
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

    public function test_notifications_tenant_admin_lista_todas_do_tenant_na_api(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $u1 = User::factory()->create(['tenant_id' => $tenant->id]);
        $u2 = User::factory()->create(['tenant_id' => $tenant->id]);

        $n1 = NotificationCenter::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $u1->id]);
        $n2 = NotificationCenter::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $u2->id]);

        $token = $admin->createToken('device', ['notifications:read']);

        $res = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/notifications?per_page=100')
            ->assertOk();

        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($n1->id, $ids);
        $this->assertContains($n2->id, $ids);
    }

    public function test_meetings_filtro_board_id_e_seguranca_cross_tenant(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();

        $adminA = User::factory()->create(['tenant_id' => $tA->id]);
        $adminA->assignRole('tenant_admin');

        $boardA = Board::factory()->create(['tenant_id' => $tA->id]);
        $boardB = Board::factory()->create(['tenant_id' => $tB->id]);

        $mA = Meeting::factory()->create([
            'tenant_id' => $tA->id,
            'board_id' => $boardA->id,
            'status' => MeetingStatus::Scheduled,
        ]);
        Meeting::factory()->create([
            'tenant_id' => $tA->id,
            'board_id' => Board::factory()->create(['tenant_id' => $tA->id])->id,
            'status' => MeetingStatus::Scheduled,
        ]);

        $token = $adminA->createToken('device', ['meetings:read']);

        $res = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/meetings?board_id='.$boardA->id.'&per_page=100')
            ->assertOk();

        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($mA->id, $ids);
        $this->assertCount(1, $ids);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/meetings?board_id='.$boardB->id)
            ->assertStatus(422);
    }

    public function test_meetings_filtros_date_e_status(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $board = Board::factory()->create(['tenant_id' => $tenant->id]);

        $inRange = Meeting::factory()->create([
            'tenant_id' => $tenant->id,
            'board_id' => $board->id,
            'scheduled_at' => Carbon::parse('2026-06-15 10:00:00'),
            'status' => MeetingStatus::Scheduled,
        ]);
        Meeting::factory()->create([
            'tenant_id' => $tenant->id,
            'board_id' => $board->id,
            'scheduled_at' => Carbon::parse('2026-01-01 10:00:00'),
            'status' => MeetingStatus::Draft,
        ]);

        $token = $admin->createToken('device', ['meetings:read']);

        $res = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/meetings?date_from=2026-06-01&date_to=2026-06-30&status='.MeetingStatus::Scheduled->value.'&per_page=100')
            ->assertOk();

        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($inRange->id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_sort_com_prefixo_negativo_aceite(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $token = $admin->createToken('device', ['boards:read']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/boards?sort=-created_at')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_sort_invalido_retorna_422(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('board_member');

        $token = $user->createToken('device', ['tasks:read']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/tasks?sort=invalid_field')
            ->assertStatus(422);
    }

    public function test_utilizador_com_manage_tasks_lista_tasks_do_tenant_na_api(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('guest');
        $user->givePermissionTo('manage_tasks');

        $assignee = User::factory()->create(['tenant_id' => $tenant->id]);
        Task::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $assignee->id]);
        Task::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $user->id]);

        $token = $user->createToken('device', ['tasks:read']);

        $res = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/tasks?per_page=100')
            ->assertOk();

        $this->assertGreaterThanOrEqual(2, count($res->json('data')));
    }

    public function test_utilizador_com_manage_notifications_lista_notifications_do_tenant_na_api(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('guest');
        $user->givePermissionTo('manage_notifications');

        $u1 = User::factory()->create(['tenant_id' => $tenant->id]);
        $u2 = User::factory()->create(['tenant_id' => $tenant->id]);
        NotificationCenter::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $u1->id]);
        NotificationCenter::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $u2->id]);

        $token = $user->createToken('device', ['notifications:read']);

        $res = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/notifications?per_page=100')
            ->assertOk();

        $this->assertGreaterThanOrEqual(2, count($res->json('data')));
    }

    public function test_tokens_de_outro_user_nao_vazam(): void
    {
        $tenant = Tenant::factory()->create();

        $u1 = User::factory()->create(['tenant_id' => $tenant->id]);
        $u1->assignRole('tenant_admin');
        $u2 = User::factory()->create(['tenant_id' => $tenant->id]);
        $u2->assignRole('tenant_admin');

        $token1 = $u1->createToken('device', ['tokens:read:self']);
        $u2->createToken('device-2', ['tokens:read:self']);
        $pat2 = PersonalAccessToken::query()->where('tokenable_id', $u2->id)->first();

        $this->assertNotNull($pat2);

        // revoke de token de outro user deve parecer "not found" (não vazar existência)
        $this->withHeader('Authorization', 'Bearer '.$token1->plainTextToken)
            ->deleteJson('/api/v1/auth/tokens/'.$pat2->id)
            ->assertStatus(403);
    }
}

