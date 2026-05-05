<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditLogsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_criacao_de_tenant_gera_audit_log(): void
    {
        $actor = User::factory()->create(['tenant_id' => null]);
        $actor->assignRole('super_admin');

        $this->actingAs($actor);

        $tenant = Tenant::factory()->create([
            'name' => 'Tenant X',
            'slug' => 'tenant-x',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::Created->value,
            'auditable_type' => Tenant::class,
            'auditable_id' => $tenant->id,
            'tenant_id' => $tenant->id,
            'user_id' => $actor->id,
        ]);
    }

    public function test_edicao_de_tenant_gera_audit_log_apenas_com_campos_permitidos(): void
    {
        $actor = User::factory()->create(['tenant_id' => null]);
        $actor->assignRole('super_admin');

        $this->actingAs($actor);

        $tenant = Tenant::factory()->create([
            'name' => 'Original',
            'slug' => 'original',
        ]);

        $tenant->update([
            'name' => 'Alterado',
        ]);

        $log = AuditLog::query()
            ->where('auditable_type', Tenant::class)
            ->where('auditable_id', $tenant->id)
            ->where('action', AuditAction::Updated->value)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(['name'], array_keys($log->new_values ?? []));
        $this->assertSame('Original', $log->old_values['name'] ?? null);
        $this->assertSame('Alterado', $log->new_values['name'] ?? null);
    }

    public function test_criacao_de_user_gera_audit_log_sem_password(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');
        $this->actingAs($actor);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'u1@example.test',
        ]);

        $log = AuditLog::query()
            ->where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('action', AuditAction::Created->value)
            ->latest('id')
            ->firstOrFail();

        $this->assertArrayNotHasKey('password', $log->new_values ?? []);
        $this->assertSame($tenant->id, $log->tenant_id);
    }

    public function test_edicao_de_user_nao_regista_password_e_nao_altera_hash_quando_vazio(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'u2@example.test',
        ]);
        $user->password = 'KeepIt99!';
        $user->save();

        $hashBefore = $user->fresh()->password;

        $this->actingAs($actor);

        $user->update([
            'name' => 'Nome novo',
        ]);

        $log = AuditLog::query()
            ->where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('action', AuditAction::Updated->value)
            ->latest('id')
            ->firstOrFail();

        $this->assertArrayNotHasKey('password', $log->old_values ?? []);
        $this->assertArrayNotHasKey('password', $log->new_values ?? []);

        $fresh = $user->fresh();
        $this->assertSame($hashBefore, $fresh->password);
        $this->assertTrue(Hash::check('KeepIt99!', $fresh->password));
    }

    public function test_tenant_admin_ve_apenas_logs_do_proprio_tenant_e_super_admin_ve_todos(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();

        $adminA = User::factory()->create(['tenant_id' => $tA->id]);
        $adminA->assignRole('tenant_admin');

        $super = User::factory()->create(['tenant_id' => null]);
        $super->assignRole('super_admin');

        // Gerar logs em ambos os tenants
        $this->actingAs($super);
        $tenantA2 = Tenant::factory()->create(['name' => 'A2']);
        $tenantB2 = Tenant::factory()->create(['name' => 'B2']);
        $this->assertNotNull($tenantA2->id);
        $this->assertNotNull($tenantB2->id);

        // tenant_admin deve ver só o seu tenant
        $this->actingAs($adminA);
        $countA = \App\Filament\Admin\Resources\AuditLogs\AuditLogResource::getEloquentQuery()->count();

        $this->actingAs($super);
        $countAll = \App\Filament\Admin\Resources\AuditLogs\AuditLogResource::getEloquentQuery()->count();

        $this->assertGreaterThan(0, $countAll);
        $this->assertLessThan($countAll, $countA);
        $this->assertSame(
            AuditLog::query()->where('tenant_id', $adminA->tenant_id)->count(),
            $countA,
        );
    }

    public function test_audit_log_policy_bloqueia_criar_editar_deletar(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $this->actingAs($admin);

        $log = AuditLog::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'action' => AuditAction::Created->value,
            'auditable_type' => null,
            'auditable_id' => null,
        ]);

        $this->assertFalse(Gate::forUser($admin)->allows('create', AuditLog::class));
        $this->assertFalse(Gate::forUser($admin)->allows('update', $log));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $log));
    }

    public function test_audit_log_nao_gera_loop(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $this->actingAs($admin);

        $before = AuditLog::query()->count();

        AuditLog::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'action' => AuditAction::Created->value,
            'auditable_type' => null,
            'auditable_id' => null,
        ]);

        $after = AuditLog::query()->count();

        $this->assertSame($before + 1, $after);
    }
}

