<?php

namespace Tests\Feature\Filament\Dashboard\Widgets;

use App\Filament\Admin\Widgets\Executive\ExecutivePrioritiesWidget;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExecutivePrioritiesWidgetTest extends TestCase
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
    public function test_renderiza_duas_seccoes_prioridades_e_atividade(): void
    {
        $this->actingAsTenantAdmin();

        // D6 obriga a manter 4 widgets; Priorities + Activity vivem aqui em secções verticais.
        Livewire::test(ExecutivePrioritiesWidget::class)
            ->assertSuccessful()
            ->assertSee(__('dashboard.executive.priorities.heading'))
            ->assertSee(__('dashboard.executive.activity.heading'));
    }

    #[Test]
    public function test_estado_vazio_apresenta_mensagens_amigaveis(): void
    {
        $this->actingAsTenantAdmin();

        // Limpar audit_logs criados pelos observers ao instanciar tenant/user.
        AuditLog::query()->delete();

        Livewire::test(ExecutivePrioritiesWidget::class)
            ->assertSee(__('dashboard.executive.priorities.empty'))
            ->assertSee(__('dashboard.executive.activity.empty'));
    }

    #[Test]
    public function test_nao_renderiza_cache_segment(): void
    {
        $this->actingAsTenantAdmin();

        Livewire::test(ExecutivePrioritiesWidget::class)
            ->assertDontSee('cache_segment')
            ->assertDontSee('cacheSegment')
            ->tap(function ($component): void {
                $html = $component->html();
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

        $this->assertFalse(ExecutivePrioritiesWidget::canView());
    }
}
