<?php

namespace Tests\Feature;

use App\Actions\Notifications\CreateNotificationAction;
use App\Actions\Notifications\MarkNotificationAsReadAction;
use App\Actions\Notifications\PersistNotificationTemplateAction;
use App\Actions\Notifications\SendNotificationAction;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\NotificationCenter;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Notifications\NotificationTemplateRenderer;
use App\Services\Notifications\NotificationTemplateResolver;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_tenant_admin_cria_template_no_proprio_tenant_e_nao_edita_global(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $t = app(PersistNotificationTemplateAction::class)->create($admin, [
            'tenant_id' => null,
            'key' => 'task_assigned',
            'name' => 'Tarefa atribuída',
            'subject' => 'Oi {{ user_name }}',
            'body' => 'Body',
            'locale' => 'pt_BR',
            'channel' => NotificationChannel::Database->value,
            'status' => 'active',
        ]);

        $this->assertSame($tenant->id, $t->tenant_id);

        $super = User::factory()->create(['tenant_id' => null, 'is_super_admin' => true]);
        $global = app(PersistNotificationTemplateAction::class)->create($super, [
            'tenant_id' => null,
            'key' => 'task_assigned',
            'name' => 'Global',
            'subject' => 'Global',
            'body' => 'Global',
            'locale' => 'pt_BR',
            'channel' => NotificationChannel::Database->value,
            'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);
        app(PersistNotificationTemplateAction::class)->update($admin, $global, [
            'key' => $global->key,
            'name' => 'Hack',
            'subject' => 'Hack',
            'body' => 'Hack',
            'locale' => $global->locale,
            'channel' => $global->channel->value,
            'status' => $global->status->value,
        ]);
    }

    public function test_template_do_tenant_sobrescreve_global(): void
    {
        $tenant = Tenant::factory()->create();
        $super = User::factory()->create(['tenant_id' => null, 'is_super_admin' => true]);
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        app(PersistNotificationTemplateAction::class)->create($super, [
            'tenant_id' => null,
            'key' => 'task_assigned',
            'name' => 'Global',
            'subject' => 'Global {{ user_name }}',
            'body' => 'Global',
            'locale' => 'pt_BR',
            'channel' => NotificationChannel::Database->value,
            'status' => 'active',
        ]);

        $tenantTemplate = app(PersistNotificationTemplateAction::class)->create($admin, [
            'key' => 'task_assigned',
            'name' => 'Tenant',
            'subject' => 'Tenant {{ user_name }}',
            'body' => 'Tenant',
            'locale' => 'pt_BR',
            'channel' => NotificationChannel::Database->value,
            'status' => 'active',
        ]);

        $resolved = app(NotificationTemplateResolver::class)->resolve($tenant->id, 'task_assigned', 'pt_BR', NotificationChannel::Database);
        $this->assertNotNull($resolved);
        $this->assertSame($tenantTemplate->id, $resolved->id);
    }

    public function test_renderer_substitui_variaveis_e_variavel_ausente_vira_vazio(): void
    {
        $template = new NotificationTemplate([
            'subject' => 'Oi {{ user_name }} {{ missing }}',
            'body' => 'Task: {{ task_title }}',
        ]);

        $r = app(NotificationTemplateRenderer::class)->render($template, [
            'user_name' => 'Ana',
            'task_title' => 'Revisar pauta',
        ]);

        $this->assertSame('Oi Ana ', $r['subject']);
        $this->assertSame('Task: Revisar pauta', $r['body']);
    }

    public function test_bloqueia_usuario_de_outro_tenant_ao_criar_notificacao(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();

        $admin = User::factory()->create(['tenant_id' => $tA->id]);
        $admin->assignRole('tenant_admin');

        $userA = User::factory()->create(['tenant_id' => $tA->id]);
        $userB = User::factory()->create(['tenant_id' => $tB->id]);

        $ok = app(CreateNotificationAction::class)->create($admin, [
            'user_id' => $userA->id,
            'title' => 'Teste',
            'channel' => NotificationChannel::Database->value,
        ]);
        $this->assertSame($tA->id, $ok->tenant_id);

        $this->expectException(ValidationException::class);
        app(CreateNotificationAction::class)->create($admin, [
            'user_id' => $userB->id,
            'title' => 'Teste',
            'channel' => NotificationChannel::Database->value,
        ]);
    }

    public function test_bloqueia_related_de_outro_tenant_ao_criar_notificacao(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();

        $admin = User::factory()->create(['tenant_id' => $tA->id]);
        $admin->assignRole('tenant_admin');

        $userA = User::factory()->create(['tenant_id' => $tA->id]);
        $docB = Document::factory()->create(['tenant_id' => $tB->id]);

        $this->expectException(ValidationException::class);
        app(CreateNotificationAction::class)->create($admin, [
            'user_id' => $userA->id,
            'title' => 'Teste',
            'channel' => NotificationChannel::Database->value,
            'related_type' => Document::class,
            'related_id' => $docB->id,
        ]);
    }

    public function test_create_notification_blocks_invalid_related_type_and_allows_whitelisted(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $document = Document::factory()->create(['tenant_id' => $tenant->id]);

        $notification = app(CreateNotificationAction::class)->create($admin, [
            'user_id' => $user->id,
            'title' => 'Relacionado permitido',
            'channel' => NotificationChannel::Database->value,
            'related_type' => Document::class,
            'related_id' => $document->id,
        ]);
        $this->assertNotNull($notification->id);
        $this->assertSame(Document::class, $notification->related_type);

        $this->expectException(ValidationException::class);
        app(CreateNotificationAction::class)->create($admin, [
            'user_id' => $user->id,
            'title' => 'Relacionado inválido',
            'channel' => NotificationChannel::Database->value,
            'related_type' => Tenant::class,
            'related_id' => $tenant->id,
        ]);
    }

    public function test_send_database_funciona_e_send_email_fake_cria_log_sem_envio_real(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $n1 = app(CreateNotificationAction::class)->create($admin, [
            'user_id' => $user->id,
            'title' => 'N1',
            'channel' => NotificationChannel::Database->value,
        ]);
        $n1 = app(SendNotificationAction::class)->send($admin, $n1);
        $this->assertSame(NotificationStatus::Sent, $n1->status);

        $n2 = app(CreateNotificationAction::class)->create($admin, [
            'user_id' => $user->id,
            'title' => 'N2',
            'channel' => NotificationChannel::Email->value,
            'metadata' => ['token' => 'secret-token'],
        ]);
        $n2 = app(SendNotificationAction::class)->send($admin, $n2);
        $this->assertSame(NotificationStatus::Sent, $n2->status);

        $payload = NotificationLog::query()->where('notification_id', $n2->id)->get()->toJson();
        $this->assertStringNotContainsString('secret-token', $payload);
    }

    public function test_mark_as_read_so_permite_propria_notificacao(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $u1 = User::factory()->create(['tenant_id' => $tenant->id]);
        $u2 = User::factory()->create(['tenant_id' => $tenant->id]);

        $n = app(CreateNotificationAction::class)->create($admin, [
            'user_id' => $u1->id,
            'title' => 'N',
            'channel' => NotificationChannel::Database->value,
        ]);

        $this->expectException(ValidationException::class);
        app(MarkNotificationAsReadAction::class)->mark($u2, $n);

        $n = app(MarkNotificationAsReadAction::class)->mark($u1, $n);
        $this->assertSame(NotificationStatus::Read, $n->status);
        $this->assertNotNull($n->read_at);
    }

    public function test_auditoria_nao_registra_body_completo(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $n = app(CreateNotificationAction::class)->create($admin, [
            'user_id' => $user->id,
            'title' => 'N',
            'body' => 'segredo: token=abc',
            'channel' => NotificationChannel::Database->value,
        ]);

        $audit = AuditLog::query()
            ->where('auditable_type', NotificationCenter::class)
            ->where('auditable_id', $n->id)
            ->get()
            ->toJson();

        $this->assertStringNotContainsString('token=abc', $audit);
    }
}

