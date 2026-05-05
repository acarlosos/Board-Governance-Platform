<?php

namespace Tests\Feature;

use App\Actions\Votes\CancelVoteAction;
use App\Actions\Votes\CastVoteAction;
use App\Actions\Votes\CloseVoteAction;
use App\Actions\Votes\OpenVoteAction;
use App\Actions\Votes\PersistVoteAction;
use App\Actions\Votes\PersistVoteOptionAction;
use App\Enums\VoteStatus;
use App\Enums\VoteType;
use App\Models\AuditLog;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteOption;
use App\Models\VoteResponse;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VotesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_tenant_admin_cria_votacao_no_proprio_tenant_e_nao_cria_para_meeting_de_outro_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $adminA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $adminA->assignRole('tenant_admin');
        $this->actingAs($adminA);

        $meetingA = Meeting::factory()->create(['tenant_id' => $tenantA->id]);
        $vote = app(PersistVoteAction::class)->create($adminA, [
            'tenant_id' => $tenantA->id,
            'meeting_id' => $meetingA->id,
            'title' => 'V1',
            'description' => null,
            'type' => VoteType::Open->value,
            'status' => VoteStatus::Draft->value,
        ]);

        $this->assertSame($tenantA->id, $vote->tenant_id);

        $meetingB = Meeting::factory()->create(['tenant_id' => $tenantB->id]);
        $this->expectException(ValidationException::class);
        app(PersistVoteAction::class)->create($adminA, [
            'tenant_id' => $tenantA->id,
            'meeting_id' => $meetingB->id,
            'title' => 'V2',
            'type' => VoteType::Open->value,
            'status' => VoteStatus::Draft->value,
        ]);
    }

    public function test_super_admin_cria_votacao_em_qualquer_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $super = User::factory()->create(['tenant_id' => null, 'is_super_admin' => true]);
        $super->assignRole('super_admin');
        $this->actingAs($super);

        $meeting = Meeting::factory()->create(['tenant_id' => $tenant->id]);

        $vote = app(PersistVoteAction::class)->create($super, [
            'tenant_id' => $tenant->id,
            'meeting_id' => $meeting->id,
            'title' => 'V',
            'type' => VoteType::Secret->value,
            'status' => VoteStatus::Draft->value,
        ]);

        $this->assertSame($tenant->id, $vote->tenant_id);
    }

    public function test_votacao_so_abre_com_pelo_menos_duas_opcoes_e_transicoes_invalidas_sao_bloqueadas(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $this->actingAs($admin);

        $vote = Vote::factory()->create(['tenant_id' => $tenant->id, 'status' => VoteStatus::Draft]);

        $this->expectException(ValidationException::class);
        app(OpenVoteAction::class)->open($admin, $vote);

        app(PersistVoteOptionAction::class)->create($admin, $vote, ['title' => 'A']);
        app(PersistVoteOptionAction::class)->create($admin, $vote, ['title' => 'B']);

        $vote = app(OpenVoteAction::class)->open($admin, $vote);
        $this->assertSame(VoteStatus::Open, $vote->status);

        $vote = app(CloseVoteAction::class)->close($admin, $vote);
        $this->assertSame(VoteStatus::Closed, $vote->status);

        $this->expectException(ValidationException::class);
        app(OpenVoteAction::class)->open($admin, $vote); // closed -> open inválido
    }

    public function test_participante_vota_na_votacao_aberta_e_nao_participante_nao_vota_e_nao_vota_duas_vezes(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $this->actingAs($admin);

        $meeting = Meeting::factory()->create(['tenant_id' => $tenant->id]);
        $participant = User::factory()->create(['tenant_id' => $tenant->id]);
        $outsider = User::factory()->create(['tenant_id' => $tenant->id]);

        MeetingParticipant::factory()->create([
            'tenant_id' => $tenant->id,
            'meeting_id' => $meeting->id,
            'user_id' => $participant->id,
        ]);

        $vote = Vote::factory()->create([
            'tenant_id' => $tenant->id,
            'meeting_id' => $meeting->id,
            'status' => VoteStatus::Draft,
        ]);

        $o1 = VoteOption::factory()->create(['tenant_id' => $tenant->id, 'vote_id' => $vote->id, 'title' => 'A']);
        $o2 = VoteOption::factory()->create(['tenant_id' => $tenant->id, 'vote_id' => $vote->id, 'title' => 'B']);

        $vote = app(OpenVoteAction::class)->open($admin, $vote);

        $this->actingAs($participant);
        $r1 = app(CastVoteAction::class)->cast($participant, $vote, [
            'vote_option_id' => $o1->id,
            'comment' => 'ok',
        ]);
        $this->assertNotNull($r1->voted_at);

        $this->expectException(ValidationException::class);
        app(CastVoteAction::class)->cast($participant, $vote, [
            'vote_option_id' => $o2->id,
        ]);

        $this->actingAs($outsider);
        $this->expectException(ValidationException::class);
        app(CastVoteAction::class)->cast($outsider, $vote, [
            'vote_option_id' => $o1->id,
        ]);
    }

    public function test_nao_vota_fora_do_periodo_e_nao_vota_em_closed_ou_cancelled_e_opcao_deve_pertencer_a_votacao(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $meeting = Meeting::factory()->create(['tenant_id' => $tenant->id]);
        $participant = User::factory()->create(['tenant_id' => $tenant->id]);
        MeetingParticipant::factory()->create(['tenant_id' => $tenant->id, 'meeting_id' => $meeting->id, 'user_id' => $participant->id]);

        $vote = Vote::factory()->create([
            'tenant_id' => $tenant->id,
            'meeting_id' => $meeting->id,
            'status' => VoteStatus::Draft,
            'starts_at' => \Illuminate\Support\Carbon::now()->addDay(),
        ]);
        $o1 = VoteOption::factory()->create(['tenant_id' => $tenant->id, 'vote_id' => $vote->id]);
        VoteOption::factory()->create(['tenant_id' => $tenant->id, 'vote_id' => $vote->id]);

        $this->actingAs($admin);
        $vote = app(OpenVoteAction::class)->open($admin, $vote);

        $this->actingAs($participant);
        $this->expectException(ValidationException::class);
        app(CastVoteAction::class)->cast($participant, $vote, ['vote_option_id' => $o1->id]);

        $this->actingAs($admin);
        $vote->starts_at = \Illuminate\Support\Carbon::now()->subHour();
        $vote->ends_at = \Illuminate\Support\Carbon::now()->subMinute();
        $vote->save();

        $this->actingAs($participant);
        $this->expectException(ValidationException::class);
        app(CastVoteAction::class)->cast($participant, $vote, ['vote_option_id' => $o1->id]);

        $this->actingAs($admin);
        $vote->ends_at = null;
        $vote->save();
        $vote = app(CloseVoteAction::class)->close($admin, $vote);

        $this->actingAs($participant);
        $this->expectException(ValidationException::class);
        app(CastVoteAction::class)->cast($participant, $vote, ['vote_option_id' => $o1->id]);

        $vote2 = Vote::factory()->create(['tenant_id' => $tenant->id, 'meeting_id' => $meeting->id, 'status' => VoteStatus::Draft]);
        VoteOption::factory()->create(['tenant_id' => $tenant->id, 'vote_id' => $vote2->id]);
        VoteOption::factory()->create(['tenant_id' => $tenant->id, 'vote_id' => $vote2->id]);
        $vote2 = app(OpenVoteAction::class)->open($admin, $vote2);
        $vote2 = app(CancelVoteAction::class)->cancel($admin, $vote2);

        $this->actingAs($participant);
        $this->expectException(ValidationException::class);
        app(CastVoteAction::class)->cast($participant, $vote2, ['vote_option_id' => $o1->id]);

        // opção de outra votação
        $vote3 = Vote::factory()->create(['tenant_id' => $tenant->id, 'meeting_id' => $meeting->id, 'status' => VoteStatus::Draft]);
        VoteOption::factory()->create(['tenant_id' => $tenant->id, 'vote_id' => $vote3->id]);
        VoteOption::factory()->create(['tenant_id' => $tenant->id, 'vote_id' => $vote3->id]);
        $vote3 = app(OpenVoteAction::class)->open($admin, $vote3);

        $this->actingAs($participant);
        $this->expectException(ValidationException::class);
        app(CastVoteAction::class)->cast($participant, $vote3, ['vote_option_id' => $o1->id]);
    }

    public function test_votacao_secreta_nao_expoe_respostas_individuais_para_nao_admin_e_auditoria_registra_eventos(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $meeting = Meeting::factory()->create(['tenant_id' => $tenant->id]);
        $participant = User::factory()->create(['tenant_id' => $tenant->id]);
        MeetingParticipant::factory()->create(['tenant_id' => $tenant->id, 'meeting_id' => $meeting->id, 'user_id' => $participant->id]);

        $vote = Vote::factory()->create([
            'tenant_id' => $tenant->id,
            'meeting_id' => $meeting->id,
            'type' => VoteType::Secret,
            'status' => VoteStatus::Draft,
        ]);
        $o1 = VoteOption::factory()->create(['tenant_id' => $tenant->id, 'vote_id' => $vote->id]);
        VoteOption::factory()->create(['tenant_id' => $tenant->id, 'vote_id' => $vote->id]);

        $this->actingAs($admin);
        $vote = app(OpenVoteAction::class)->open($admin, $vote);

        $this->actingAs($participant);
        $response = app(CastVoteAction::class)->cast($participant, $vote, ['vote_option_id' => $o1->id, 'comment' => 'sensivel']);

        // policy: em votação secreta, não-admin não pode ver resposta individual (evita expor user_id em listagens comuns)
        $this->assertFalse($participant->can('view', $response));

        // auditoria: deve existir evento vote_cast sem comment
        $last = AuditLog::query()->latest('id')->first();
        $this->assertNotNull($last);
        $json = json_encode([$last->old_values, $last->new_values]);
        $this->assertIsString($json);
        $this->assertStringContainsString('vote_cast', $json);
        $this->assertStringNotContainsString('sensivel', $json);
    }
}

