<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Boards\PersistBoardAction;
use App\Actions\Boards\PersistBoardMemberAction;
use App\Enums\AuditAction;
use App\Enums\BoardMemberRole;
use App\Enums\BoardMemberStatus;
use App\Enums\BoardStatus;
use App\Filament\Admin\Resources\Boards\BoardResource;
use App\Models\AuditLog;
use App\Models\Board;
use App\Models\BoardMember;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BoardsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_tenant_admin_cria_board_apenas_no_proprio_tenant(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();

        $admin = User::factory()->create(['tenant_id' => $tA->id]);
        $admin->assignRole('tenant_admin');

        $created = app(PersistBoardAction::class)->create($admin, [
            'tenant_id' => $tB->id,
            'name' => 'Board A',
            'description' => null,
            'status' => BoardStatus::Active->value,
        ]);

        $this->assertSame($tA->id, $created->tenant_id);
    }

    public function test_super_admin_pode_criar_board_para_qualquer_tenant(): void
    {
        $tA = Tenant::factory()->create();
        $super = User::factory()->create(['tenant_id' => null]);
        $super->assignRole('super_admin');

        $created = app(PersistBoardAction::class)->create($super, [
            'tenant_id' => $tA->id,
            'name' => 'Plataforma',
            'description' => 'X',
            'status' => BoardStatus::Active->value,
        ]);

        $this->assertSame($tA->id, $created->tenant_id);
    }

    public function test_user_de_outro_tenant_nao_acessa_board_via_policy(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();

        $board = Board::factory()->create(['tenant_id' => $tA->id]);

        $adminB = User::factory()->create(['tenant_id' => $tB->id]);
        $adminB->assignRole('tenant_admin');

        $this->assertFalse(Gate::forUser($adminB)->allows('view', $board));
        $this->assertFalse(Gate::forUser($adminB)->allows('update', $board));
    }

    public function test_board_member_ve_apenas_boards_onde_e_membro_ativo_na_query_do_resource(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('board_member');

        $boardA = Board::factory()->create(['tenant_id' => $tenant->id, 'name' => 'A']);
        $boardB = Board::factory()->create(['tenant_id' => $tenant->id, 'name' => 'B']);

        BoardMember::factory()->create([
            'tenant_id' => $tenant->id,
            'board_id' => $boardA->id,
            'user_id' => $user->id,
            'role' => BoardMemberRole::Member,
            'status' => BoardMemberStatus::Active,
        ]);

        Auth::login($user);
        $names = BoardResource::getEloquentQuery()->pluck('name')->all();
        Auth::logout();

        $this->assertSame(['A'], $names);
        $this->assertSame($boardB->name, 'B'); // sanity
    }

    public function test_guest_nao_pode_gerir_boards(): void
    {
        $tenant = Tenant::factory()->create();
        $guest = User::factory()->create(['tenant_id' => $tenant->id]);
        $guest->assignRole('guest');

        $this->assertFalse(Gate::forUser($guest)->allows('viewAny', Board::class));
        $this->assertFalse(Gate::forUser($guest)->allows('create', Board::class));
    }

    public function test_criacao_e_edicao_de_board_gera_audit_log(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $this->actingAs($admin);

        $board = Board::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Auditável',
            'description' => null,
            'status' => BoardStatus::Active,
            'created_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::Created->value,
            'auditable_type' => Board::class,
            'auditable_id' => $board->id,
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
        ]);

        $board->update(['status' => BoardStatus::Archived]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::StatusChanged->value,
            'auditable_type' => Board::class,
            'auditable_id' => $board->id,
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_criacao_e_edicao_de_board_member_gera_audit_log(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $board = Board::factory()->create(['tenant_id' => $tenant->id]);
        $memberUser = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($admin);

        $member = app(PersistBoardMemberAction::class)->create($admin, $board, [
            'user_id' => $memberUser->id,
            'role' => BoardMemberRole::Member->value,
            'status' => BoardMemberStatus::Active->value,
            'joined_at' => null,
            'left_at' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::Created->value,
            'auditable_type' => BoardMember::class,
            'auditable_id' => $member->id,
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
        ]);

        $updated = app(PersistBoardMemberAction::class)->update($admin, $member, [
            'role' => BoardMemberRole::Secretary->value,
            'status' => BoardMemberStatus::Active->value,
            'joined_at' => null,
            'left_at' => null,
        ]);

        $this->assertSame(BoardMemberRole::Secretary, $updated->role);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::Updated->value,
            'auditable_type' => BoardMember::class,
            'auditable_id' => $member->id,
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_service_impede_membro_de_outro_tenant_no_board(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();

        $admin = User::factory()->create(['tenant_id' => $tA->id]);
        $admin->assignRole('tenant_admin');

        $board = Board::factory()->create(['tenant_id' => $tA->id]);
        $userB = User::factory()->create(['tenant_id' => $tB->id]);

        $this->expectException(ValidationException::class);

        app(PersistBoardMemberAction::class)->create($admin, $board, [
            'user_id' => $userB->id,
            'role' => BoardMemberRole::Member->value,
            'status' => BoardMemberStatus::Active->value,
        ]);
    }

    public function test_service_nao_permite_duplicidade_de_membro_ativo_no_mesmo_board(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $board = Board::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        app(PersistBoardMemberAction::class)->create($admin, $board, [
            'user_id' => $user->id,
            'role' => BoardMemberRole::Member->value,
            'status' => BoardMemberStatus::Active->value,
        ]);

        $this->expectException(ValidationException::class);

        app(PersistBoardMemberAction::class)->create($admin, $board, [
            'user_id' => $user->id,
            'role' => BoardMemberRole::Observer->value,
            'status' => BoardMemberStatus::Active->value,
        ]);
    }

    public function test_board_resource_query_respeita_tenant_para_tenant_admin(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();

        Board::factory()->count(2)->create(['tenant_id' => $tA->id]);
        Board::factory()->count(3)->create(['tenant_id' => $tB->id]);

        $adminA = User::factory()->create(['tenant_id' => $tA->id]);
        $adminA->assignRole('tenant_admin');

        Auth::login($adminA);
        $count = BoardResource::getEloquentQuery()->count();
        Auth::logout();

        $this->assertSame(2, $count);
    }

    public function test_board_member_query_respeita_tenant_scope(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();

        $adminA = User::factory()->create(['tenant_id' => $tA->id]);
        $adminA->assignRole('tenant_admin');

        $boardA = Board::factory()->create(['tenant_id' => $tA->id]);
        $userA = User::factory()->create(['tenant_id' => $tA->id]);
        BoardMember::factory()->create(['tenant_id' => $tA->id, 'board_id' => $boardA->id, 'user_id' => $userA->id]);

        $boardB = Board::factory()->create(['tenant_id' => $tB->id]);
        $userB = User::factory()->create(['tenant_id' => $tB->id]);
        BoardMember::factory()->create(['tenant_id' => $tB->id, 'board_id' => $boardB->id, 'user_id' => $userB->id]);

        Auth::login($adminA);
        $count = BoardMember::query()->count();
        Auth::logout();

        $this->assertSame(1, $count);
    }
}

