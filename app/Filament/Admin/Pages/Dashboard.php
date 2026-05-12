<?php

namespace App\Filament\Admin\Pages;

use App\Enums\DashboardMetricsPeriod;
use Filament\Pages\Dashboard as FilamentDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class Dashboard extends FilamentDashboard
{
    protected static ?string $title = null;

    /**
     * Período partilhado (D5): valor canónico do enum; o selector vive no Hero, mas a página
     * faz bootstrap via `mount()` + `dispatch` para widgets lazy montarem já alinhados.
     */
    public string $period = '';

    public function mount(): void
    {
        if ($this->period === '') {
            $this->period = DashboardMetricsPeriod::ThisMonth->value;
        }

        $this->dispatch('dashboard:period-changed', period: $this->period);
    }

    #[On('dashboard:period-changed')]
    public function onDashboardPeriodChanged(string $period): void
    {
        $this->period = $period;
    }

    /**
     * Acesso à página do dashboard.
     *
     * 19A.7: com widgets executivos activos, qualquer utilizador **autenticado** pode abrir
     * esta página (evita 403 pós-login para contas sem role/tenant ainda configurados).
     * Os blocos executivos e dados agregados continuam fechados por
     * `Executive*Widget::canView()` (gate `view_executive_dashboard`) e policies nos providers.
     * Com flag desactivada mantém-se o legado: qualquer auth acede ao dashboard legacy.
     */
    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.page.nav_label');
    }

    public function getHeading(): string|Htmlable|null
    {
        return __('dashboard.page.title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('dashboard.page.subtitle');
    }

    /**
     * Grelha responsiva dos widgets estatísticos (1 → 2 → 3 colunas).
     *
     * @return int | array<string, ?int>
     */
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'xl' => 3,
            '2xl' => 3,
        ];
    }

    /**
     * Título consistente entre cabeçalho e marcadores.
     */
    public function getTitle(): string|Htmlable
    {
        return __('dashboard.page.title');
    }
}
