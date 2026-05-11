<?php

namespace Tests\Unit\Auth\Gates;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ViewExecutiveDashboardGateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function test_super_admin_com_tenant_acede(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'is_super_admin' => true]);

        $this->assertTrue(Gate::forUser($user)->allows('view_executive_dashboard'));
    }

    #[Test]
    public function test_super_admin_sem_tenant_acede(): void
    {
        $user = User::factory()->create(['tenant_id' => null, 'is_super_admin' => true]);

        $this->assertTrue(Gate::forUser($user)->allows('view_executive_dashboard'));
    }

    #[Test]
    public function test_tenant_admin_acede_via_view_reports(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $this->assertTrue(Gate::forUser($user)->allows('view_executive_dashboard'));
    }

    #[Test]
    public function test_board_member_acede_via_view_reports(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('board_member');

        $this->assertTrue(Gate::forUser($user)->allows('view_executive_dashboard'));
    }

    #[Test]
    public function test_executive_acede_via_view_reports(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('executive');

        $this->assertTrue(Gate::forUser($user)->allows('view_executive_dashboard'));
    }

    #[Test]
    public function test_guest_acede_via_view_reports(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('guest');

        $this->assertTrue(Gate::forUser($user)->allows('view_executive_dashboard'));
    }

    #[Test]
    public function test_user_com_role_custom_sem_view_reports_nao_acede(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $role = Role::query()->firstOrCreate(['name' => 'custom_no_reports', 'guard_name' => 'web']);
        $user->assignRole($role);

        $this->assertFalse(Gate::forUser($user)->allows('view_executive_dashboard'));
    }

    #[Test]
    public function test_user_sem_qualquer_role_nao_acede(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertFalse(Gate::forUser($user)->allows('view_executive_dashboard'));
    }

    #[Test]
    public function test_user_sem_tenant_id_e_nao_super_admin_nao_acede_mesmo_com_view_reports(): void
    {
        $user = User::factory()->create(['tenant_id' => null, 'is_super_admin' => false]);
        $user->assignRole('guest'); // possui view_reports via seeder

        $this->assertFalse(Gate::forUser($user)->allows('view_executive_dashboard'));
    }

    #[Test]
    public function test_anonimo_nao_acede(): void
    {
        $this->assertFalse(Gate::forUser(null)->allows('view_executive_dashboard'));
    }

    #[Test]
    public function test_gate_esta_registado(): void
    {
        $this->assertTrue(Gate::has('view_executive_dashboard'));
    }
}
