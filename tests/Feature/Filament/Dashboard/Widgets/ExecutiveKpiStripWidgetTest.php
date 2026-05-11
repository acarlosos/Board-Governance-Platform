<?php

namespace Tests\Feature\Filament\Dashboard\Widgets;

use App\Filament\Admin\Widgets\Executive\ExecutiveKpiStripWidget;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExecutiveKpiStripWidgetTest extends TestCase
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

        Livewire::test(ExecutiveKpiStripWidget::class)
            ->assertSuccessful()
            ->assertSee(__('dashboard.executive.kpis.heading'));
    }

    #[Test]
    public function test_nao_renderiza_cache_segment(): void
    {
        $this->actingAsTenantAdmin();

        Livewire::test(ExecutiveKpiStripWidget::class)
            ->assertDontSee('cache_segment')
            ->assertDontSee('cacheSegment');
    }
}
