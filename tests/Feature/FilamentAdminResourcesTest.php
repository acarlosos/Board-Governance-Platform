<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Filament\PersistPanelUserAction;
use App\Enums\UserStatus;
use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FilamentAdminResourcesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_acessa_rota_do_tenant_resource(): void
    {
        $user = User::factory()->create(['tenant_id' => null]);
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get(TenantResource::getUrl())
            ->assertSuccessful();
    }

    public function test_tenant_admin_nao_acessa_rota_do_tenant_resource(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $this->actingAs($user)
            ->get(TenantResource::getUrl())
            ->assertForbidden();
    }

    public function test_super_admin_lista_todos_os_utilizadores_na_query_do_user_resource(): void
    {
        $ta = Tenant::factory()->create();
        $tb = Tenant::factory()->create();
        User::factory()->count(2)->create(['tenant_id' => $ta->id]);
        User::factory()->count(3)->create(['tenant_id' => $tb->id]);

        $super = User::factory()->create(['tenant_id' => null]);
        $super->assignRole('super_admin');

        Auth::login($super);
        $count = UserResource::getEloquentQuery()->count();
        Auth::logout();

        $this->assertSame(6, $count);
    }

    public function test_tenant_admin_lista_apenas_utilizadores_do_proprio_tenant_na_query(): void
    {
        $ta = Tenant::factory()->create();
        $tb = Tenant::factory()->create();
        User::factory()->count(2)->create(['tenant_id' => $ta->id]);
        User::factory()->count(4)->create(['tenant_id' => $tb->id]);

        $admin = User::factory()->create(['tenant_id' => $ta->id]);
        $admin->assignRole('tenant_admin');

        Auth::login($admin);
        $count = UserResource::getEloquentQuery()->count();
        Auth::logout();

        $this->assertSame(3, $count);
    }

    public function test_tenant_admin_cria_utilizador_no_proprio_tenant_mesmo_com_outro_tenant_id_no_payload(): void
    {
        $ta = Tenant::factory()->create();
        $tb = Tenant::factory()->create();

        $admin = User::factory()->create(['tenant_id' => $ta->id]);
        $admin->assignRole('tenant_admin');

        $created = app(PersistPanelUserAction::class)->create($admin, [
            'name' => 'Novo utilizador',
            'email' => 'novo@example.test',
            'password' => 'password123',
            'locale' => 'pt_BR',
            'status' => UserStatus::Active->value,
            'tenant_id' => $tb->id,
            'is_super_admin' => true,
            'roles' => ['guest'],
        ]);

        $this->assertSame($ta->id, $created->tenant_id);
        $this->assertFalse($created->is_super_admin);
        $this->assertTrue($created->hasRole('guest'));
    }

    public function test_tenant_admin_nao_pode_atribuir_role_super_admin(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $this->expectException(ValidationException::class);

        app(PersistPanelUserAction::class)->create($admin, [
            'name' => 'X',
            'email' => 'x@example.test',
            'password' => 'password123',
            'locale' => 'pt_BR',
            'status' => UserStatus::Active->value,
            'roles' => ['super_admin'],
        ]);
    }

    public function test_tenant_admin_nao_define_is_super_admin(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $created = app(PersistPanelUserAction::class)->create($admin, [
            'name' => 'Y',
            'email' => 'y@example.test',
            'password' => 'password123',
            'locale' => 'pt_BR',
            'status' => UserStatus::Active->value,
            'is_super_admin' => true,
            'roles' => ['guest'],
        ]);

        $this->assertFalse($created->is_super_admin);
    }

    public function test_password_e_hasheado_na_criacao(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $plain = 'MySecret123';

        $created = app(PersistPanelUserAction::class)->create($admin, [
            'name' => 'Z',
            'email' => 'z@example.test',
            'password' => $plain,
            'locale' => 'pt_BR',
            'status' => UserStatus::Active->value,
            'roles' => ['guest'],
        ]);

        $this->assertTrue(Hash::check($plain, $created->password));
    }

    public function test_edicao_sem_password_nao_altera_hash_existente(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'membro@example.test',
        ]);
        $user->password = 'KeepIt99!';
        $user->save();

        $hashBefore = $user->fresh()->password;

        app(PersistPanelUserAction::class)->update($admin, $user, [
            'name' => 'Nome alterado',
            'email' => $user->email,
            'locale' => $user->locale,
            'status' => $user->status->value,
        ]);

        $fresh = $user->fresh();
        $this->assertSame($hashBefore, $fresh->password);
        $this->assertTrue(Hash::check('KeepIt99!', $fresh->password));
    }
}
