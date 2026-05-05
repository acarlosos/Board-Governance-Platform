<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Meetings\CancelMeetingAction;
use App\Actions\Meetings\CompleteMeetingAction;
use App\Actions\Meetings\PersistMeetingAction;
use App\Actions\Meetings\PersistMeetingParticipantAction;
use App\Actions\Meetings\StartMeetingAction;
use App\Enums\AuditAction;
use App\Enums\MeetingParticipantRole;
use App\Enums\MeetingParticipantStatus;
use App\Enums\MeetingStatus;
use App\Filament\Admin\Resources\Meetings\MeetingResource;
use App\Models\AuditLog;
use App\Models\Board;
use App\Models\BoardMember;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MeetingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_tenant_admin_cria_reuniao_apenas_no_proprio_tenant(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();

        $boardA = Board::factory()->create(['tenant_id' => $tA->id]);
        $boardB = Board::factory()->create(['tenant_id' => $tB->id]);

        $admin = User::factory()->create(['tenant_id' => $tA->id]);
        $admin->assignRole('tenant_admin');

        $created = app(PersistMeetingAction::class)->create($admin, [
            'tenant_id' => $tB->id,
            'board_id' => $boardA->id,
            'title' => 'Reunião A',
            'description' => null,
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'starts_at' => null,
            'ends_at' => null,
            'video_conference_url' => null,
            'status' => MeetingStatus::Draft->value,
        ]);

        $this->assertSame($tA->id, $created->tenant_id);

        $this->expectException(ValidationException::class);
        app(PersistMeetingAction::class)->create($admin, [
            'tenant_id' => $tA->id,
            'board_id' => $boardB->id, // outro tenant
            'title' => 'X',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'status' => MeetingStatus::Draft->value,
        ]);
    }

    public function test_super_admin_cria_reuniao_em_qualquer_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $board = Board::factory()->create(['tenant_id' => $tenant->id]);

        $super = User::factory()->create(['tenant_id' => null]);
        $super->assignRole('super_admin');

        $created = app(PersistMeetingAction::class)->create($super, [
            'tenant_id' => $tenant->id,
            'board_id' => $board->id,
            'title' => 'Global',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'status' => MeetingStatus::Draft->value,
        ]);

        $this->assertSame($tenant->id, $created->tenant_id);
    }

    public function test_board_member_ve_apenas_reunioes_do_board_onde_e_membro_ativo(): void
    {
        $tenant = Tenant::factory()->create();
        $boardA = Board::factory()->create(['tenant_id' => $tenant->id, 'name' => 'A']);
        $boardB = Board::factory()->create(['tenant_id' => $tenant->id, 'name' => 'B']);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('board_member');

        BoardMember::factory()->create([
            'tenant_id' => $tenant->id,
            'board_id' => $boardA->id,
            'user_id' => $user->id,
            'status' => \App\Enums\BoardMemberStatus::Active,
        ]);

        Meeting::factory()->create(['tenant_id' => $tenant->id, 'board_id' => $boardA->id, 'title' => 'M1']);
        Meeting::factory()->create(['tenant_id' => $tenant->id, 'board_id' => $boardB->id, 'title' => 'M2']);

        Auth::login($user);
        $titles = MeetingResource::getEloquentQuery()->pluck('title')->all();
        Auth::logout();

        $this->assertSame(['M1'], $titles);
    }

    public function test_participante_ve_reuniao_onde_esta_vinculado(): void
    {
        $tenant = Tenant::factory()->create();
        $board = Board::factory()->create(['tenant_id' => $tenant->id]);
        $meeting = Meeting::factory()->create(['tenant_id' => $tenant->id, 'board_id' => $board->id, 'title' => 'M']);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('guest');

        MeetingParticipant::factory()->create([
            'tenant_id' => $tenant->id,
            'meeting_id' => $meeting->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('view', $meeting));
    }

    public function test_guest_nao_gerencia_reunioes(): void
    {
        $tenant = Tenant::factory()->create();
        $guest = User::factory()->create(['tenant_id' => $tenant->id]);
        $guest->assignRole('guest');

        $this->assertFalse(Gate::forUser($guest)->allows('viewAny', Meeting::class));
        $this->assertFalse(Gate::forUser($guest)->allows('create', Meeting::class));
    }

    public function test_transicoes_validas_e_invalidas_de_status_sao_controladas_por_actions(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $board = Board::factory()->create(['tenant_id' => $tenant->id]);
        $meeting = Meeting::factory()->create([
            'tenant_id' => $tenant->id,
            'board_id' => $board->id,
            'status' => MeetingStatus::Scheduled,
        ]);

        $started = app(StartMeetingAction::class)->start($admin, $meeting);
        $this->assertSame(MeetingStatus::InProgress, $started->status);

        $completed = app(CompleteMeetingAction::class)->complete($admin, $started);
        $this->assertSame(MeetingStatus::Completed, $completed->status);

        $this->expectException(ValidationException::class);
        app(StartMeetingAction::class)->start($admin, $completed);
    }

    public function test_cancel_so_em_draft_ou_scheduled(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $board = Board::factory()->create(['tenant_id' => $tenant->id]);
        $inProgress = Meeting::factory()->create(['tenant_id' => $tenant->id, 'board_id' => $board->id, 'status' => MeetingStatus::InProgress]);

        $this->expectException(ValidationException::class);
        app(CancelMeetingAction::class)->cancel($admin, $inProgress);
    }

    public function test_auditoria_de_meeting_e_status_changed(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $this->actingAs($admin);

        $board = Board::factory()->create(['tenant_id' => $tenant->id]);

        $meeting = Meeting::query()->create([
            'tenant_id' => $tenant->id,
            'board_id' => $board->id,
            'title' => 'Audit',
            'description' => null,
            'scheduled_at' => now()->addDay(),
            'status' => MeetingStatus::Scheduled,
            'created_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::Created->value,
            'auditable_type' => Meeting::class,
            'auditable_id' => $meeting->id,
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
        ]);

        app(StartMeetingAction::class)->start($admin, $meeting);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::StatusChanged->value,
            'auditable_type' => Meeting::class,
            'auditable_id' => $meeting->id,
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_participante_de_outro_tenant_e_bloqueado_e_duplicidade_ativa_e_bloqueada(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();

        $admin = User::factory()->create(['tenant_id' => $tA->id]);
        $admin->assignRole('tenant_admin');

        $board = Board::factory()->create(['tenant_id' => $tA->id]);
        $meeting = Meeting::factory()->create(['tenant_id' => $tA->id, 'board_id' => $board->id]);

        $userB = User::factory()->create(['tenant_id' => $tB->id]);

        $this->expectException(ValidationException::class);
        app(PersistMeetingParticipantAction::class)->create($admin, $meeting, [
            'user_id' => $userB->id,
            'role' => MeetingParticipantRole::Participant->value,
            'status' => MeetingParticipantStatus::Invited->value,
        ]);

        $userA = User::factory()->create(['tenant_id' => $tA->id]);
        app(PersistMeetingParticipantAction::class)->create($admin, $meeting, [
            'user_id' => $userA->id,
            'role' => MeetingParticipantRole::Participant->value,
            'status' => MeetingParticipantStatus::Invited->value,
        ]);

        $this->expectException(ValidationException::class);
        app(PersistMeetingParticipantAction::class)->create($admin, $meeting, [
            'user_id' => $userA->id,
            'role' => MeetingParticipantRole::Guest->value,
            'status' => MeetingParticipantStatus::Confirmed->value,
        ]);
    }
}

