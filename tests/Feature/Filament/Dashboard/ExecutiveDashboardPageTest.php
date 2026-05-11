<?php

namespace Tests\Feature\Filament\Dashboard;

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Widgets\Executive\ExecutiveHeroWidget;
use App\Filament\Admin\Widgets\Executive\ExecutiveKpiStripWidget;
use App\Filament\Admin\Widgets\Executive\ExecutiveOperationsWidget;
use App\Filament\Admin\Widgets\Executive\ExecutivePrioritiesWidget;
use App\Filament\Admin\Widgets\MeetingsStatsWidget;
use App\Filament\Admin\Widgets\TasksStatsWidget;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExecutiveDashboardPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function userWithReports(): User
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        return $user;
    }

    #[Test]
    public function test_can_access_retorna_true_para_user_com_gate(): void
    {
        config(['board.dashboard.use_executive_widgets' => true]);

        $user = $this->userWithReports();
        $this->actingAs($user);

        $this->assertTrue(Dashboard::canAccess());
    }

    #[Test]
    public function test_can_access_retorna_false_para_user_sem_gate(): void
    {
        config(['board.dashboard.use_executive_widgets' => true]);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        // sem role nem permissions
        $this->actingAs($user);

        $this->assertFalse(Dashboard::canAccess());
    }

    #[Test]
    public function test_can_access_retorna_false_para_anonimo(): void
    {
        config(['board.dashboard.use_executive_widgets' => true]);

        $this->assertFalse(Dashboard::canAccess());
    }

    #[Test]
    public function test_can_access_mantem_comportamento_legado_com_flag_false(): void
    {
        config(['board.dashboard.use_executive_widgets' => false]);

        // Qualquer utilizador autenticado tem acesso ao painel legado.
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->actingAs($user);

        $this->assertTrue(Dashboard::canAccess());
    }

    #[Test]
    public function test_widgets_executivos_invisiveis_quando_flag_false(): void
    {
        config(['board.dashboard.use_executive_widgets' => false]);

        $user = $this->userWithReports();
        $this->actingAs($user);

        $this->assertFalse(ExecutiveHeroWidget::canView());
        $this->assertFalse(ExecutiveKpiStripWidget::canView());
        $this->assertFalse(ExecutiveOperationsWidget::canView());
        $this->assertFalse(ExecutivePrioritiesWidget::canView());
    }

    #[Test]
    public function test_widgets_executivos_visiveis_quando_flag_true_e_gate_ok(): void
    {
        config(['board.dashboard.use_executive_widgets' => true]);

        $user = $this->userWithReports();
        $this->actingAs($user);

        $this->assertTrue(ExecutiveHeroWidget::canView());
        $this->assertTrue(ExecutiveKpiStripWidget::canView());
        $this->assertTrue(ExecutiveOperationsWidget::canView());
        $this->assertTrue(ExecutivePrioritiesWidget::canView());
    }

    #[Test]
    public function test_widgets_legados_visiveis_quando_flag_false(): void
    {
        config(['board.dashboard.use_executive_widgets' => false]);

        $user = $this->userWithReports();
        $this->actingAs($user);

        $this->assertTrue(TasksStatsWidget::canView());
        $this->assertTrue(MeetingsStatsWidget::canView());
    }

    #[Test]
    public function test_widgets_legados_invisiveis_quando_flag_true(): void
    {
        config(['board.dashboard.use_executive_widgets' => true]);

        $user = $this->userWithReports();
        $this->actingAs($user);

        $this->assertFalse(TasksStatsWidget::canView());
        $this->assertFalse(MeetingsStatsWidget::canView());
    }
}
