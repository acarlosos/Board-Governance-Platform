<?php

namespace Tests\Feature;

use App\Actions\Integrations\DisableIntegrationAction;
use App\Actions\Integrations\EnableIntegrationAction;
use App\Actions\Integrations\PersistIntegrationAction;
use App\Actions\Integrations\TestIntegrationAction;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Enums\IntegrationTestStatus;
use App\Enums\IntegrationType;
use App\Models\AuditLog;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class IntegrationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_tenant_admin_cria_integracao_no_proprio_tenant_e_nao_escolhe_tenant_id(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        $integration = app(PersistIntegrationAction::class)->create($actor, [
            'tenant_id' => $tenant->id + 999,
            'type' => IntegrationType::Email->value,
            'provider' => IntegrationProvider::Smtp->value,
            'name' => 'SMTP principal',
            'config' => [
                'host' => 'smtp.example.test',
                'port' => 587,
                'username' => 'user',
                'password' => 'my-secret',
                'from_address' => 'noreply@example.test',
            ],
        ]);

        $this->assertSame($tenant->id, $integration->tenant_id);
    }

    public function test_tenant_admin_nao_consegue_definir_tenant_id_de_outro_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $actor = User::factory()->create(['tenant_id' => $tenantA->id]);
        $actor->assignRole('tenant_admin');

        $integration = app(PersistIntegrationAction::class)->create($actor, [
            'tenant_id' => $tenantB->id,
            'type' => IntegrationType::Email->value,
            'provider' => IntegrationProvider::Smtp->value,
            'name' => 'SMTP',
            'config' => [
                'host' => 'smtp.example.test',
                'port' => 587,
                'username' => 'user',
                'password' => 'secret',
                'from_address' => 'noreply@example.test',
            ],
        ]);

        $this->assertSame($tenantA->id, $integration->tenant_id);
    }

    public function test_super_admin_cria_integracao_em_qualquer_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => null, 'is_super_admin' => true]);

        $integration = app(PersistIntegrationAction::class)->create($actor, [
            'tenant_id' => $tenant->id,
            'type' => IntegrationType::Email->value,
            'provider' => IntegrationProvider::Smtp->value,
            'name' => 'SMTP',
            'config' => [
                'host' => 'smtp.example.test',
                'port' => 587,
                'username' => 'user',
                'password' => 'secret',
                'from_address' => 'noreply@example.test',
            ],
        ]);

        $this->assertSame($tenant->id, $integration->tenant_id);
    }

    public function test_config_e_criptografado_no_banco(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        $integration = app(PersistIntegrationAction::class)->create($actor, [
            'type' => IntegrationType::Email->value,
            'provider' => IntegrationProvider::Smtp->value,
            'name' => 'SMTP',
            'config' => [
                'host' => 'smtp.example.test',
                'port' => 587,
                'username' => 'user',
                'password' => 'my-secret',
                'from_address' => 'noreply@example.test',
            ],
        ]);

        $raw = (string) DB::table('integrations')->where('id', $integration->id)->value('config');
        $this->assertStringNotContainsString('my-secret', $raw);
        $this->assertStringNotContainsString('smtp.example.test', $raw);
    }

    public function test_secrets_nao_aparecem_em_integration_logs_ou_audit_logs(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        $integration = app(PersistIntegrationAction::class)->create($actor, [
            'type' => IntegrationType::Email->value,
            'provider' => IntegrationProvider::Smtp->value,
            'name' => 'SMTP',
            'config' => [
                'host' => 'smtp.example.test',
                'port' => 587,
                'username' => 'user',
                'password' => 'my-secret',
                'from_address' => 'noreply@example.test',
            ],
        ]);

        app(TestIntegrationAction::class)->test($actor, $integration);

        $logPayload = IntegrationLog::query()->where('integration_id', $integration->id)->get()->toJson();
        $this->assertStringNotContainsString('my-secret', $logPayload);
        $this->assertStringNotContainsString('smtp.example.test', $logPayload);

        $auditPayload = AuditLog::query()->where('auditable_type', Integration::class)->where('auditable_id', $integration->id)->get()->toJson();
        $this->assertStringNotContainsString('my-secret', $auditPayload);
        $this->assertStringNotContainsString('smtp.example.test', $auditPayload);
    }

    public function test_editar_integracao_com_secret_vazio_mantem_secret_existente(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        $integration = app(PersistIntegrationAction::class)->create($actor, [
            'type' => IntegrationType::Email->value,
            'provider' => IntegrationProvider::Smtp->value,
            'name' => 'SMTP',
            'config' => [
                'host' => 'smtp.example.test',
                'port' => 587,
                'username' => 'user',
                'password' => 'my-secret',
                'from_address' => 'noreply@example.test',
            ],
        ]);

        $integration = app(PersistIntegrationAction::class)->update($actor, $integration, [
            'type' => IntegrationType::Email->value,
            'provider' => IntegrationProvider::Smtp->value,
            'name' => 'SMTP atualizado',
            'config' => [
                'host' => 'smtp.example.test',
                'port' => 587,
                'username' => 'user',
                'password' => '', // mantém
                'from_address' => 'noreply@example.test',
            ],
        ]);

        $this->assertSame('my-secret', (string) ($integration->config['password'] ?? ''));
    }

    public function test_provider_invalido_e_bloqueado_e_config_obrigatoria_e_validada(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        $this->expectException(ValidationException::class);

        app(PersistIntegrationAction::class)->create($actor, [
            'type' => IntegrationType::Email->value,
            'provider' => 'invalid_provider',
            'name' => 'X',
            'config' => [],
        ]);
    }

    public function test_testintegrationaction_atualiza_last_test_status(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        $integration = app(PersistIntegrationAction::class)->create($actor, [
            'type' => IntegrationType::Email->value,
            'provider' => IntegrationProvider::Smtp->value,
            'name' => 'SMTP',
            'config' => [
                'host' => 'smtp.example.test',
                'port' => 587,
                'username' => 'user',
                'password' => 'secret',
                'from_address' => 'noreply@example.test',
            ],
        ]);

        $integration = app(TestIntegrationAction::class)->test($actor, $integration);

        $this->assertSame(IntegrationTestStatus::Success, $integration->last_test_status);
        $this->assertNotNull($integration->last_tested_at);
    }

    public function test_enableintegrationaction_so_ativa_apos_teste_success_e_disable_desativa(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        $integration = app(PersistIntegrationAction::class)->create($actor, [
            'type' => IntegrationType::Email->value,
            'provider' => IntegrationProvider::Smtp->value,
            'name' => 'SMTP',
            'config' => [
                'host' => 'smtp.example.test',
                'port' => 587,
                'username' => 'user',
                'password' => 'secret',
                'from_address' => 'noreply@example.test',
            ],
        ]);

        $this->expectException(ValidationException::class);
        app(EnableIntegrationAction::class)->enable($actor, $integration);
    }

    public function test_enable_e_disable_fluxo_feliz(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        $integration = app(PersistIntegrationAction::class)->create($actor, [
            'type' => IntegrationType::Email->value,
            'provider' => IntegrationProvider::Smtp->value,
            'name' => 'SMTP',
            'config' => [
                'host' => 'smtp.example.test',
                'port' => 587,
                'username' => 'user',
                'password' => 'secret',
                'from_address' => 'noreply@example.test',
            ],
        ]);

        $integration = app(TestIntegrationAction::class)->test($actor, $integration);
        $integration = app(EnableIntegrationAction::class)->enable($actor, $integration);
        $this->assertSame(IntegrationStatus::Active, $integration->status);

        $integration = app(DisableIntegrationAction::class)->disable($actor, $integration);
        $this->assertSame(IntegrationStatus::Inactive, $integration->status);
    }
}

