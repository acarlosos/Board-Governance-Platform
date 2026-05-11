<?php

namespace Tests\Feature\Filament\Dashboard\Widgets;

use App\Filament\Admin\Widgets\Executive\ExecutiveOperationsWidget;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExecutiveOperationsWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['board.dashboard.use_executive_widgets' => true]);
    }

    private function actingAsTenantAdmin(): User
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');
        $this->actingAs($user);

        return $user;
    }

    #[Test]
    public function test_renderiza_sem_erro_para_user_valido(): void
    {
        $this->actingAsTenantAdmin();

        Livewire::test(ExecutiveOperationsWidget::class)
            ->assertSuccessful()
            ->assertSee(__('dashboard.executive.operations.heading'));
    }

    #[Test]
    public function test_apresenta_link_para_relatorios_operacionais_quando_user_tem_view_reports(): void
    {
        $this->actingAsTenantAdmin();

        Livewire::test(ExecutiveOperationsWidget::class)
            ->assertSee(__('dashboard.executive.operations.cta_reports'));
    }

    #[Test]
    public function test_nao_renderiza_cache_segment(): void
    {
        $this->actingAsTenantAdmin();

        Livewire::test(ExecutiveOperationsWidget::class)
            ->assertDontSee('cache_segment')
            ->assertDontSee('cacheSegment');
    }
}
