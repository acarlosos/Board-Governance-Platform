<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\InitialTenantSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthPermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_roles_e_permissoes_sao_criadas_pelo_seeder(): void
    {
        $this->assertSame(14, \Spatie\Permission\Models\Permission::query()->count());
        $this->assertSame(5, \Spatie\Permission\Models\Role::query()->count());
        $this->assertTrue(\Spatie\Permission\Models\Role::query()->where('name', 'super_admin')->exists());
    }

    public function test_super_admin_pode_gerir_tenants(): void
    {
        $user = User::factory()->create(['tenant_id' => null, 'is_super_admin' => false]);
        $user->assignRole('super_admin');

        $this->assertTrue(Gate::forUser($user)->allows('viewAny', Tenant::class));
        $this->assertTrue(Gate::forUser($user)->allows('create', Tenant::class));
    }

    public function test_tenant_admin_nao_pode_gerir_tenants(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $this->assertFalse(Gate::forUser($user)->allows('viewAny', Tenant::class));
        $this->assertFalse(Gate::forUser($user)->allows('create', Tenant::class));
    }

    public function test_manage_tenants_atribuida_por_engano_nao_autoriza_sem_is_super_admin(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'is_super_admin' => false]);
        $user->assignRole('tenant_admin');
        $user->givePermissionTo('manage_tenants');

        $this->assertFalse($user->isSuperAdmin());
        $this->assertTrue($user->can('manage_tenants'));
        $this->assertFalse(Gate::forUser($user)->allows('viewAny', Tenant::class));
        $this->assertFalse(Gate::forUser($user)->allows('create', Tenant::class));
    }

    public function test_tenant_admin_pode_gerir_utilizadores_do_proprio_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $membro = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertTrue(Gate::forUser($admin)->allows('view', $membro));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $membro));
    }

    public function test_tenant_admin_nao_pode_gerir_utilizadores_de_outro_tenant(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tA->id]);
        $admin->assignRole('tenant_admin');

        $outro = User::factory()->create(['tenant_id' => $tB->id]);

        $this->assertFalse(Gate::forUser($admin)->allows('view', $outro));
        $this->assertFalse(Gate::forUser($admin)->allows('update', $outro));
    }

    public function test_guest_nao_pode_gerir_utilizadores(): void
    {
        $tenant = Tenant::factory()->create();
        $guest = User::factory()->create(['tenant_id' => $tenant->id]);
        $guest->assignRole('guest');

        $outro = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertFalse(Gate::forUser($guest)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($guest)->allows('view', $outro));
    }

    public function test_flag_is_super_admin_funciona_como_bootstrap(): void
    {
        $user = User::factory()->create(['is_super_admin' => true, 'tenant_id' => null]);

        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->shouldBypassTenantScope());
        $this->assertTrue(Gate::forUser($user)->allows('viewAny', Tenant::class));
    }

    public function test_role_super_admin_permite_bypass_de_tenant_scope_sem_flag(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'is_super_admin' => false]);
        $user->assignRole('super_admin');

        $user = $user->fresh();

        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->shouldBypassTenantScope());
    }

    public function test_super_admin_seeder_cria_utilizador_global_com_role_e_tenants(): void
    {
        $this->seed(SuperAdminSeeder::class);

        $user = User::query()->withoutGlobalScopes()->where('email', 'root@localhost')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->tenant_id);
        $this->assertTrue($user->is_super_admin);
        $this->assertTrue($user->hasRole('super_admin'));
        $this->assertTrue(Hash::check('AlterarEstaSenhaRoot1!', $user->password));
        $this->assertTrue(Gate::forUser($user)->allows('viewAny', Tenant::class));
    }

    public function test_database_seeder_cria_admin_do_tenant_e_super_admin_distintos(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenantAdmin = User::query()->withoutGlobalScopes()->where('email', 'admin@localhost')->first();
        $superAdmin = User::query()->withoutGlobalScopes()->where('email', 'root@localhost')->first();

        $this->assertNotNull($tenantAdmin);
        $this->assertNotNull($superAdmin);
        $this->assertNotSame($tenantAdmin->id, $superAdmin->id);
        $this->assertNotNull($tenantAdmin->tenant_id);
        $this->assertTrue($tenantAdmin->hasRole('tenant_admin'));
        $this->assertFalse($tenantAdmin->is_super_admin);
        $this->assertNull($superAdmin->tenant_id);
        $this->assertTrue($superAdmin->hasRole('super_admin'));
    }

    public function test_seeders_rejeitam_mesmo_email_para_admin_e_super_admin(): void
    {
        $this->expectException(\RuntimeException::class);

        putenv('SEED_ADMIN_EMAIL=duplicado@test.local');
        putenv('SEED_SUPER_ADMIN_EMAIL=duplicado@test.local');

        try {
            $this->seed(InitialTenantSeeder::class);
        } finally {
            putenv('SEED_ADMIN_EMAIL');
            putenv('SEED_SUPER_ADMIN_EMAIL');
        }
    }
}
