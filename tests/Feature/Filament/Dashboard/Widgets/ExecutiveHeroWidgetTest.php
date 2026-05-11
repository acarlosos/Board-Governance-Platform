<?php

namespace Tests\Feature\Filament\Dashboard\Widgets;

use App\Filament\Admin\Widgets\Executive\ExecutiveHeroWidget;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExecutiveHeroWidgetTest extends TestCase
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

        Livewire::test(ExecutiveHeroWidget::class)
            ->assertSuccessful()
            ->assertSee(__('dashboard.executive.hero.heading'));
    }

    #[Test]
    public function test_nao_renderiza_cache_segment(): void
    {
        $this->actingAsTenantAdmin();

        // cacheSegment é telemetria interna (formato t_<id>/global/none).
        // Não deve aparecer renderizado para o utilizador.
        Livewire::test(ExecutiveHeroWidget::class)
            ->assertDontSee('cache_segment')
            ->assertDontSee('cacheSegment')
            ->tap(function ($component): void {
                $html = $component->html();
                // Segmento de cache tipo `t_{id}` — não confundir com `last_30_days` (contém `t_`).
                $this->assertDoesNotMatchRegularExpression('/\bt_\d+/', $html);
                $this->assertDoesNotMatchRegularExpression('/\bcacheSegment\b/', $html);
                $this->assertStringNotContainsString('>global<', $html);
            });
    }

    #[Test]
    public function test_can_view_respeita_gate(): void
    {
        config(['board.dashboard.use_executive_widgets' => true]);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->actingAs($user);

        $this->assertFalse(ExecutiveHeroWidget::canView());
    }

    #[Test]
    public function test_period_changed_propaga_evento_aos_demais_widgets(): void
    {
        $this->actingAsTenantAdmin();

        Livewire::test(ExecutiveHeroWidget::class)
            ->set('period', 'last_30_days')
            ->assertDispatched('dashboard:period-changed', period: 'last_30_days');
    }

    #[Test]
    public function test_recebe_evento_period_changed_e_actualiza_property(): void
    {
        $this->actingAsTenantAdmin();

        Livewire::test(ExecutiveHeroWidget::class)
            ->call('onPeriodChanged', 'all_time')
            ->assertSet('period', 'all_time');
    }
}
