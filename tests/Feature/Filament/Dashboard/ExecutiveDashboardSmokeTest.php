<?php

namespace Tests\Feature\Filament\Dashboard;

use App\Filament\Admin\Pages\Dashboard;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExecutiveDashboardSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function test_page_renderiza_sem_erro_com_flag_true_e_tenant_vazio(): void
    {
        config(['board.dashboard.use_executive_widgets' => true]);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');
        $this->actingAs($user);

        Livewire::test(Dashboard::class)->assertSuccessful();
    }

    #[Test]
    public function test_page_renderiza_sem_erro_com_flag_false_legado_intacto(): void
    {
        config(['board.dashboard.use_executive_widgets' => false]);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');
        $this->actingAs($user);

        Livewire::test(Dashboard::class)->assertSuccessful();
    }
}
