<?php

namespace Tests\Feature;

use App\Actions\Tasks\AddTaskCommentAction;
use App\Actions\Tasks\CancelTaskAction;
use App\Actions\Tasks\CompleteTaskAction;
use App\Actions\Tasks\PersistTaskAction;
use App\Actions\Tasks\StartTaskAction;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\AuditLog;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Database\Seeders\RolesAndPermissionsSeeder;
use Tests\TestCase;

class TasksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_tenant_admin_cria_task_e_forca_tenant_id_do_actor(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        $task = app(PersistTaskAction::class)->create($actor, [
            'tenant_id' => $tenant->id + 999,
            'title' => 'Acompanhar pendência',
            'description' => 'Detalhes',
            'status' => TaskStatus::Pending->value,
            'priority' => TaskPriority::Normal->value,
        ]);

        $this->assertSame($tenant->id, $task->tenant_id);
        $this->assertSame($actor->id, $task->created_by);
    }

    public function test_assigned_to_deve_ser_do_mesmo_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $actor = User::factory()->create(['tenant_id' => $tenantA->id]);
        $actor->assignRole('tenant_admin');

        $otherTenantUser = User::factory()->create(['tenant_id' => $tenantB->id]);

        $this->expectException(ValidationException::class);

        app(PersistTaskAction::class)->create($actor, [
            'title' => 'Pendência',
            'status' => TaskStatus::Pending->value,
            'priority' => TaskPriority::Normal->value,
            'assigned_to' => $otherTenantUser->id,
        ]);
    }

    public function test_related_deve_ser_do_mesmo_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $actor = User::factory()->create(['tenant_id' => $tenantA->id]);
        $actor->assignRole('tenant_admin');

        $meetingOtherTenant = Meeting::factory()->create(['tenant_id' => $tenantB->id]);

        $this->expectException(ValidationException::class);

        app(PersistTaskAction::class)->create($actor, [
            'title' => 'Pendência',
            'status' => TaskStatus::Pending->value,
            'priority' => TaskPriority::Normal->value,
            'related_type' => Meeting::class,
            'related_id' => $meetingOtherTenant->id,
        ]);
    }

    public function test_status_controlado_por_actions_e_transicoes_invalidas_falham(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        $task = Task::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Normal,
        ]);

        // não pode concluir direto de pending
        $this->expectException(ValidationException::class);
        app(CompleteTaskAction::class)->complete($actor, $task);
    }

    public function test_actions_ajustam_completed_at_corretamente(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        $task = Task::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Normal,
            'completed_at' => null,
        ]);

        $task = app(StartTaskAction::class)->start($actor, $task);
        $this->assertSame(TaskStatus::InProgress, $task->status);
        $this->assertNull($task->completed_at);

        $task = app(CompleteTaskAction::class)->complete($actor, $task);
        $this->assertSame(TaskStatus::Completed, $task->status);
        $this->assertNotNull($task->completed_at);

        // cancelar após completed não é permitido pelo state machine
        $this->expectException(ValidationException::class);
        app(CancelTaskAction::class)->cancel($actor, $task->fresh());
    }

    public function test_cancel_de_pending_funciona(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        $task = Task::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Normal,
        ]);

        $task = app(CancelTaskAction::class)->cancel($actor, $task);

        $this->assertSame(TaskStatus::Cancelled, $task->status);
        $this->assertNull($task->completed_at);
    }

    public function test_comentario_respeita_permissao_da_task(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $assignee = User::factory()->create(['tenant_id' => $tenant->id]);
        $viewer = User::factory()->create(['tenant_id' => $tenant->id]);

        $task = Task::factory()->create([
            'tenant_id' => $tenant->id,
            'assigned_to' => $assignee->id,
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Normal,
        ]);

        // viewer sem roles/permissão não deve conseguir comentar (não consegue view)
        $this->expectException(ValidationException::class);
        app(AddTaskCommentAction::class)->add($viewer, $task, 'oi');
    }

    public function test_historico_registra_eventos_relevantes(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        $task = app(PersistTaskAction::class)->create($actor, [
            'title' => 'Pendência',
            'status' => TaskStatus::Pending->value,
            'priority' => TaskPriority::Normal->value,
        ]);

        $this->assertDatabaseHas('task_history', [
            'task_id' => $task->id,
            'action' => 'created',
        ]);

        $task = app(StartTaskAction::class)->start($actor, $task);
        $this->assertDatabaseHas('task_history', [
            'task_id' => $task->id,
            'action' => 'status_changed',
        ]);
    }

    public function test_auditoria_cria_logs_sem_vazar_comment(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        $task = app(PersistTaskAction::class)->create($actor, [
            'title' => 'Pendência',
            'status' => TaskStatus::Pending->value,
            'priority' => TaskPriority::Normal->value,
        ]);

        $task = app(StartTaskAction::class)->start($actor, $task);

        app(AddTaskCommentAction::class)->add($actor, $task, 'texto sensível');

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'auditable_type' => Task::class,
            'auditable_id' => $task->id,
        ]);

        $last = AuditLog::query()
            ->where('auditable_type', Task::class)
            ->where('auditable_id', $task->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($last);
        $payload = json_encode([$last->old_values, $last->new_values]);
        $this->assertIsString($payload);
        $this->assertStringNotContainsString('texto sensível', $payload);
    }
}

