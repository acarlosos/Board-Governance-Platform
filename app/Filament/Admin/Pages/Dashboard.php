<?php

namespace App\Filament\Admin\Pages;

use App\Enums\DashboardMetricsPeriod;
use App\Models\User;
use Filament\Pages\Dashboard as FilamentDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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
     * 19A.7: quando o conjunto executivo está activo (`board.dashboard.use_executive_widgets`)
     * exigimos o gate `view_executive_dashboard` (19A.6). Caso contrário, mantém-se o
     * comportamento legado (qualquer utilizador autenticado do painel).
     */
    public static function canAccess(): bool
    {
        if (! (bool) config('board.dashboard.use_executive_widgets', false)) {
            return Auth::check();
        }

        $user = Auth::user();

        return $user instanceof User
            && Gate::forUser($user)->allows('view_executive_dashboard');
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
