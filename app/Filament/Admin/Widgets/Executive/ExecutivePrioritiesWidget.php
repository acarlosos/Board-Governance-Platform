<?php

namespace App\Filament\Admin\Widgets\Executive;

use App\Enums\DashboardMetricsPeriod;
use App\Models\User;
use App\Services\Dashboard\Executive\ExecutiveDashboardReadService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;

/**
 * D6: Priorities + Activity são consolidados num único widget (duas secções verticais)
 * para manter o limite de 4 widgets Livewire na página do dashboard executivo.
 */
class ExecutivePrioritiesWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.executive.priorities';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 13;

    // D7: feeds per-user são deferidos.
    protected static bool $isLazy = true;

    public string $period = '';

    public function mount(): void
    {
        if ($this->period === '') {
            $this->period = DashboardMetricsPeriod::ThisMonth->value;
        }
    }

    #[On('dashboard:period-changed')]
    public function onPeriodChanged(string $period): void
    {
        $this->period = $period;
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && (bool) config('board.dashboard.use_executive_widgets', false)
            && Gate::forUser($user)->allows('view_executive_dashboard');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return ['snapshot' => null];
        }

        $period = DashboardMetricsPeriod::tryFrom($this->period)
            ?? DashboardMetricsPeriod::ThisMonth;

        $snapshot = app(ExecutiveDashboardReadService::class)->read($user, $period);

        return ['snapshot' => $snapshot];
    }
}
