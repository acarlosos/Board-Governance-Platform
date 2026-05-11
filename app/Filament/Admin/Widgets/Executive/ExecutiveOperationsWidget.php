<?php

namespace App\Filament\Admin\Widgets\Executive;

use App\Enums\DashboardMetricsPeriod;
use App\Filament\Admin\Pages\OperationalReports;
use App\Models\User;
use App\Services\Dashboard\Executive\ExecutiveDashboardReadService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;

class ExecutiveOperationsWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.executive.operations';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 12;

    // D7: bloco secundário deferido para reduzir TTFB.
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
            return ['snapshot' => null, 'reportsUrl' => null];
        }

        $period = DashboardMetricsPeriod::tryFrom($this->period)
            ?? DashboardMetricsPeriod::ThisMonth;

        $snapshot = app(ExecutiveDashboardReadService::class)->read($user, $period);

        $reportsUrl = OperationalReports::canAccess()
            ? OperationalReports::getUrl(panel: 'admin')
            : null;

        return [
            'snapshot' => $snapshot,
            'reportsUrl' => $reportsUrl,
        ];
    }
}
