<?php

namespace Tests\Feature;

use App\Actions\Integrations\EnableIntegrationAction;
use App\Actions\Integrations\PersistIntegrationAction;
use App\Actions\Integrations\TestIntegrationAction;
use App\Actions\Signatures\CancelSignatureRequestAction;
use App\Actions\Signatures\PersistSignatureRequestAction;
use App\Actions\Signatures\PersistSignatureSignerAction;
use App\Actions\Signatures\RejectSignatureRequestAction;
use App\Actions\Signatures\SendSignatureRequestAction;
use App\Actions\Signatures\SignSignatureRequestAction;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Enums\IntegrationTestStatus;
use App\Enums\IntegrationType;
use App\Enums\SignatureProvider;
use App\Enums\SignatureRequestStatus;
use App\Enums\SignatureSignerStatus;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Integration;
use App\Models\Minute;
use App\Models\SignatureEvent;
use App\Models\SignatureRequest;
use App\Models\SignatureRequestSigner;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SignaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_tenant_admin_cria_solicitacao_no_proprio_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $doc = Document::factory()->create(['tenant_id' => $tenant->id]);

        $req = app(PersistSignatureRequestAction::class)->create($admin, [
            'tenant_id' => $tenant->id + 999,
            'signable_type' => Document::class,
            'signable_id' => $doc->id,
            'provider' => SignatureProvider::Internal->value,
            'title' => 'Assinatura',
            'message' => 'Mensagem',
        ]);

        $this->assertSame($tenant->id, $req->tenant_id);
        $this->assertSame(SignatureRequestStatus::Draft, $req->status);
    }

    public function test_nao_cria_solicitacao_para_signable_de_outro_tenant(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();

        $admin = User::factory()->create(['tenant_id' => $tA->id]);
        $admin->assignRole('tenant_admin');

        $docOther = Document::factory()->create(['tenant_id' => $tB->id]);

        $this->expectException(ValidationException::class);

        app(PersistSignatureRequestAction::class)->create($admin, [
            'signable_type' => Document::class,
            'signable_id' => $docOther->id,
            'provider' => SignatureProvider::Internal->value,
            'title' => 'Assinatura',
        ]);
    }

    public function test_provider_docusign_exige_integracao_ativa_do_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $minute = Minute::factory()->create(['tenant_id' => $tenant->id]);

        // sem integração => falha
        $this->expectException(ValidationException::class);
        app(PersistSignatureRequestAction::class)->create($admin, [
            'signable_type' => Minute::class,
            'signable_id' => $minute->id,
            'provider' => SignatureProvider::DocuSign->value,
            'title' => 'DocuSign',
        ]);
    }

    public function test_signer_user_id_deve_ser_do_mesmo_tenant_e_signer_externo_eh_permitido(): void
    {
        $tenant = Tenant::factory()->create();
        $other = Tenant::factory()->create();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $doc = Document::factory()->create(['tenant_id' => $tenant->id]);

        $req = app(PersistSignatureRequestAction::class)->create($admin, [
            'signable_type' => Document::class,
            'signable_id' => $doc->id,
            'provider' => SignatureProvider::Internal->value,
            'title' => 'Assinatura',
        ]);

        $userOther = User::factory()->create(['tenant_id' => $other->id]);

        $this->expectException(ValidationException::class);
        app(PersistSignatureSignerAction::class)->create($admin, $req, [
            'user_id' => $userOther->id,
            'name' => 'X',
            'email' => 'x@example.test',
        ]);

        $external = app(PersistSignatureSignerAction::class)->create($admin, $req, [
            'user_id' => null,
            'name' => 'Externo',
            'email' => 'externo@example.test',
        ]);
        $this->assertNotNull($external->id);
    }

    public function test_envio_exige_pelo_menos_um_signer_e_muda_draft_para_sent(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $doc = Document::factory()->create(['tenant_id' => $tenant->id]);
        $req = app(PersistSignatureRequestAction::class)->create($admin, [
            'signable_type' => Document::class,
            'signable_id' => $doc->id,
            'provider' => SignatureProvider::Internal->value,
            'title' => 'Assinatura',
        ]);

        $this->expectException(ValidationException::class);
        app(SendSignatureRequestAction::class)->send($admin, $req);
    }

    public function test_internal_assina_e_quando_todos_assinam_vira_completed(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $u1 = User::factory()->create(['tenant_id' => $tenant->id]);
        $u2 = User::factory()->create(['tenant_id' => $tenant->id]);

        $doc = Document::factory()->create(['tenant_id' => $tenant->id]);
        $req = app(PersistSignatureRequestAction::class)->create($admin, [
            'signable_type' => Document::class,
            'signable_id' => $doc->id,
            'provider' => SignatureProvider::Internal->value,
            'title' => 'Assinatura',
        ]);

        app(PersistSignatureSignerAction::class)->create($admin, $req, [
            'user_id' => $u1->id,
            'name' => 'U1',
            'email' => $u1->email,
        ]);
        app(PersistSignatureSignerAction::class)->create($admin, $req, [
            'user_id' => $u2->id,
            'name' => 'U2',
            'email' => $u2->email,
        ]);

        $req = app(SendSignatureRequestAction::class)->send($admin, $req);
        $this->assertSame(SignatureRequestStatus::Sent, $req->status);

        $s1 = $req->signers()->where('user_id', $u1->id)->firstOrFail();
        $s2 = $req->signers()->where('user_id', $u2->id)->firstOrFail();

        app(SignSignatureRequestAction::class)->sign($u1, $s1);
        $req = $req->fresh();
        $this->assertSame(SignatureRequestStatus::Sent, $req->status);

        app(SignSignatureRequestAction::class)->sign($u2, $s2);
        $req = $req->fresh();
        $this->assertSame(SignatureRequestStatus::Completed, $req->status);
        $this->assertNotNull($req->completed_at);
    }

    public function test_rejeicao_muda_request_para_failed_e_cancelamento_para_cancelled(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $u1 = User::factory()->create(['tenant_id' => $tenant->id]);
        $doc = Document::factory()->create(['tenant_id' => $tenant->id]);

        $req = app(PersistSignatureRequestAction::class)->create($admin, [
            'signable_type' => Document::class,
            'signable_id' => $doc->id,
            'provider' => SignatureProvider::Internal->value,
            'title' => 'Assinatura',
        ]);

        app(PersistSignatureSignerAction::class)->create($admin, $req, [
            'user_id' => $u1->id,
            'name' => 'U1',
            'email' => $u1->email,
        ]);

        $req = app(SendSignatureRequestAction::class)->send($admin, $req);
        $signer = $req->signers()->firstOrFail();

        app(RejectSignatureRequestAction::class)->reject($u1, $signer, 'não');
        $req = $req->fresh();
        $this->assertSame(SignatureRequestStatus::Failed, $req->status);

        // nova req para testar cancel
        $req2 = app(PersistSignatureRequestAction::class)->create($admin, [
            'signable_type' => Document::class,
            'signable_id' => $doc->id,
            'provider' => SignatureProvider::Internal->value,
            'title' => 'Assinatura2',
        ]);
        app(PersistSignatureSignerAction::class)->create($admin, $req2, [
            'user_id' => $u1->id,
            'name' => 'U1',
            'email' => $u1->email,
        ]);
        $req2 = app(SendSignatureRequestAction::class)->send($admin, $req2);

        $req2 = app(CancelSignatureRequestAction::class)->cancel($admin, $req2);
        $this->assertSame(SignatureRequestStatus::Cancelled, $req2->status);
        $this->assertNotNull($req2->cancelled_at);
    }

    public function test_transicoes_invalidas_sao_bloqueadas_e_signer_nao_assina_outro_signer(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $u1 = User::factory()->create(['tenant_id' => $tenant->id]);
        $u2 = User::factory()->create(['tenant_id' => $tenant->id]);
        $doc = Document::factory()->create(['tenant_id' => $tenant->id]);

        $req = app(PersistSignatureRequestAction::class)->create($admin, [
            'signable_type' => Document::class,
            'signable_id' => $doc->id,
            'provider' => SignatureProvider::Internal->value,
            'title' => 'Assinatura',
        ]);
        app(PersistSignatureSignerAction::class)->create($admin, $req, [
            'user_id' => $u1->id,
            'name' => 'U1',
            'email' => $u1->email,
        ]);
        app(PersistSignatureSignerAction::class)->create($admin, $req, [
            'user_id' => $u2->id,
            'name' => 'U2',
            'email' => $u2->email,
        ]);

        $req = app(SendSignatureRequestAction::class)->send($admin, $req);
        $s1 = $req->signers()->where('user_id', $u1->id)->firstOrFail();
        $s2 = $req->signers()->where('user_id', $u2->id)->firstOrFail();

        // u1 não pode assinar s2
        $this->expectException(ValidationException::class);
        app(SignSignatureRequestAction::class)->sign($u1, $s2);
    }

    public function test_eventos_sao_registrados_e_audit_logs_nao_expoem_message_ou_metadata(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $doc = Document::factory()->create(['tenant_id' => $tenant->id]);
        $req = app(PersistSignatureRequestAction::class)->create($admin, [
            'signable_type' => Document::class,
            'signable_id' => $doc->id,
            'provider' => SignatureProvider::Internal->value,
            'title' => 'Assinatura',
            'message' => 'segredo: token=abc',
        ]);

        $this->assertDatabaseHas('signature_events', [
            'signature_request_id' => $req->id,
            'action' => 'created',
        ]);

        $auditPayload = AuditLog::query()
            ->where('auditable_type', SignatureRequest::class)
            ->where('auditable_id', $req->id)
            ->get()
            ->toJson();

        $this->assertStringNotContainsString('token=abc', $auditPayload);
        $this->assertStringNotContainsString('segredo', $auditPayload);
    }
}

