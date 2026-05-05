<?php

namespace Tests\Feature;

use App\Actions\Minutes\ApproveMinuteAction;
use App\Actions\Minutes\ArchiveMinuteAction;
use App\Actions\Minutes\CreateMinuteVersionAction;
use App\Actions\Minutes\PersistMinuteAction;
use App\Actions\Minutes\RejectMinuteAction;
use App\Actions\Minutes\ReopenRejectedMinuteAction;
use App\Actions\Minutes\SubmitMinuteForReviewAction;
use App\Enums\MinuteApprovalStatus;
use App\Enums\MinuteStatus;
use App\Models\AuditLog;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\Minute;
use App\Models\MinuteApproval;
use App\Models\MinuteVersion;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MinutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_criacao_de_ata_no_tenant_correto(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $this->actingAs($admin);

        $meeting = Meeting::factory()->create(['tenant_id' => $tenant->id]);

        $minute = app(PersistMinuteAction::class)->create($admin, [
            'tenant_id' => $tenant->id,
            'meeting_id' => $meeting->id,
            'title' => 'Ata 1',
            'content' => 'Conteúdo',
            'status' => MinuteStatus::Draft->value,
        ]);

        $this->assertSame($tenant->id, $minute->tenant_id);
        $this->assertSame($meeting->id, $minute->meeting_id);
    }

    public function test_nao_cria_ata_para_meeting_de_outro_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $adminA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $adminA->assignRole('tenant_admin');
        $this->actingAs($adminA);

        $meetingB = Meeting::factory()->create(['tenant_id' => $tenantB->id]);

        $this->expectException(ValidationException::class);
        app(PersistMinuteAction::class)->create($adminA, [
            'tenant_id' => $tenantA->id,
            'meeting_id' => $meetingB->id,
            'title' => 'Ata',
            'content' => 'Conteúdo',
            'status' => MinuteStatus::Draft->value,
        ]);
    }

    public function test_participante_ve_ata_e_usuario_de_outro_tenant_nao_ve(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $adminA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $adminA->assignRole('tenant_admin');

        $participant = User::factory()->create(['tenant_id' => $tenantA->id]);
        $outsider = User::factory()->create(['tenant_id' => $tenantA->id]);
        $otherTenantUser = User::factory()->create(['tenant_id' => $tenantB->id]);

        $meeting = Meeting::factory()->create(['tenant_id' => $tenantA->id]);
        MeetingParticipant::factory()->create([
            'tenant_id' => $tenantA->id,
            'meeting_id' => $meeting->id,
            'user_id' => $participant->id,
        ]);

        $this->actingAs($adminA);
        $minute = Minute::factory()->create([
            'tenant_id' => $tenantA->id,
            'meeting_id' => $meeting->id,
            'status' => MinuteStatus::Draft,
        ]);

        $this->actingAs($participant);
        $this->assertTrue($participant->can('view', $minute));
        $this->assertFalse($outsider->can('view', $minute));
        $this->assertFalse($otherTenantUser->can('view', $minute));
    }

    public function test_criacao_de_versao_incrementa_e_atualiza_current_version_id(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $this->actingAs($admin);

        $minute = Minute::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => MinuteStatus::Draft,
        ]);

        $v1 = app(CreateMinuteVersionAction::class)->create($admin, $minute, [
            'content' => 'v1',
            'changes_summary' => 'init',
        ]);

        $minute->refresh();
        $this->assertSame(1, $v1->version_number);
        $this->assertSame($v1->id, $minute->current_version_id);

        $v2 = app(CreateMinuteVersionAction::class)->create($admin, $minute, [
            'content' => 'v2',
            'changes_summary' => 'upd',
        ]);

        $minute->refresh();
        $this->assertSame(2, $v2->version_number);
        $this->assertSame($v2->id, $minute->current_version_id);
    }

    public function test_envio_para_revisao_cria_approvals_para_participantes_e_muda_status(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $this->actingAs($admin);

        $meeting = Meeting::factory()->create(['tenant_id' => $tenant->id]);
        $u1 = User::factory()->create(['tenant_id' => $tenant->id]);
        $u2 = User::factory()->create(['tenant_id' => $tenant->id]);
        MeetingParticipant::factory()->create(['tenant_id' => $tenant->id, 'meeting_id' => $meeting->id, 'user_id' => $u1->id]);
        MeetingParticipant::factory()->create(['tenant_id' => $tenant->id, 'meeting_id' => $meeting->id, 'user_id' => $u2->id]);

        $minute = Minute::factory()->create(['tenant_id' => $tenant->id, 'meeting_id' => $meeting->id, 'status' => MinuteStatus::Draft]);

        $minute = app(SubmitMinuteForReviewAction::class)->submit($admin, $minute);
        $minute = app(SubmitMinuteForReviewAction::class)->submit($admin, $minute); // idempotente

        $this->assertSame(MinuteStatus::InReview, $minute->status);
        $this->assertSame(2, MinuteApproval::query()->where('minute_id', $minute->id)->count());
        $this->assertSame(
            [MinuteApprovalStatus::Pending->value, MinuteApprovalStatus::Pending->value],
            MinuteApproval::query()->where('minute_id', $minute->id)->orderBy('user_id')->pluck('status')->map(fn ($s) => $s instanceof MinuteApprovalStatus ? $s->value : (string) $s)->all(),
        );
    }

    public function test_aprovacao_de_todos_muda_status_para_approved_e_rejeicao_muda_para_rejected(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $this->actingAs($admin);

        $meeting = Meeting::factory()->create(['tenant_id' => $tenant->id]);
        $u1 = User::factory()->create(['tenant_id' => $tenant->id]);
        $u2 = User::factory()->create(['tenant_id' => $tenant->id]);
        MeetingParticipant::factory()->create(['tenant_id' => $tenant->id, 'meeting_id' => $meeting->id, 'user_id' => $u1->id]);
        MeetingParticipant::factory()->create(['tenant_id' => $tenant->id, 'meeting_id' => $meeting->id, 'user_id' => $u2->id]);

        $minute = Minute::factory()->create(['tenant_id' => $tenant->id, 'meeting_id' => $meeting->id, 'status' => MinuteStatus::Draft]);
        $minute = app(SubmitMinuteForReviewAction::class)->submit($admin, $minute);

        $this->actingAs($u1);
        $minute = app(ApproveMinuteAction::class)->approve($u1, $minute);
        $this->assertSame(MinuteStatus::InReview, $minute->status);

        $this->expectException(ValidationException::class);
        app(ApproveMinuteAction::class)->approve($u1, $minute); // não aprova duas vezes

        $this->actingAs($u2);
        $minute = app(ApproveMinuteAction::class)->approve($u2, $minute);
        $this->assertSame(MinuteStatus::Approved, $minute->status);

        // cenário rejeição
        $minute2 = Minute::factory()->create(['tenant_id' => $tenant->id, 'meeting_id' => $meeting->id, 'status' => MinuteStatus::Draft]);
        $minute2 = app(SubmitMinuteForReviewAction::class)->submit($admin, $minute2);

        $this->actingAs($u1);
        $minute2 = app(RejectMinuteAction::class)->reject($u1, $minute2);
        $this->assertSame(MinuteStatus::Rejected, $minute2->status);

        $this->expectException(ValidationException::class);
        app(RejectMinuteAction::class)->reject($u1, $minute2); // não rejeita duas vezes
    }

    public function test_state_machine_impede_transicoes_invalidas(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $this->actingAs($admin);

        $minute = Minute::factory()->create(['tenant_id' => $tenant->id, 'status' => MinuteStatus::Draft]);

        $this->expectException(ValidationException::class);
        app(ArchiveMinuteAction::class)->archive($admin, $minute); // draft -> archived não permitido
    }

    public function test_concorrencia_simulada_gera_version_numbers_sequenciais(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $this->actingAs($admin);

        $minute = Minute::factory()->create(['tenant_id' => $tenant->id, 'status' => MinuteStatus::Draft]);

        for ($i = 0; $i < 5; $i++) {
            app(CreateMinuteVersionAction::class)->create($admin, $minute, [
                'content' => 'v'.($i + 1),
                'changes_summary' => 's'.($i + 1),
            ]);
            $minute->refresh();
        }

        $numbers = MinuteVersion::query()->where('minute_id', $minute->id)->orderBy('version_number')->pluck('version_number')->all();
        $this->assertSame([1, 2, 3, 4, 5], $numbers);
    }

    public function test_rejected_reabre_para_draft_e_edicao_e_bloqueada_em_in_review_e_approved(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $this->actingAs($admin);

        $minute = Minute::factory()->create(['tenant_id' => $tenant->id, 'status' => MinuteStatus::Draft]);
        $minute->status = MinuteStatus::InReview;
        $minute->save();

        $this->expectException(ValidationException::class);
        app(PersistMinuteAction::class)->update($admin, $minute, [
            'tenant_id' => $tenant->id,
            'meeting_id' => $minute->meeting_id,
            'title' => 'X',
            'content' => 'Y',
        ]);
    }

    public function test_auditoria_nao_registra_conteudo_completo(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $this->actingAs($admin);

        $minute = Minute::factory()->create(['tenant_id' => $tenant->id, 'status' => MinuteStatus::Draft, 'content' => 'segredo muito grande']);

        $version = app(CreateMinuteVersionAction::class)->create($admin, $minute, [
            'content' => 'conteudo super secreto',
            'changes_summary' => 'resumo',
        ]);

        $last = AuditLog::query()->latest('id')->first();
        $this->assertNotNull($last);

        $json = json_encode([$last->old_values, $last->new_values]);
        $this->assertIsString($json);
        $this->assertStringNotContainsString('conteudo super secreto', $json);
        $this->assertStringNotContainsString('segredo muito grande', $json);
        $this->assertStringNotContainsString('content', $json);
        $this->assertStringNotContainsString((string) $version->content, $json);
    }
}

