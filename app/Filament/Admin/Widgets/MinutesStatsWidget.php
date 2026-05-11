<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\DashboardMetricsPeriod;
use App\Services\Dashboard\DashboardMetricsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MinutesStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 22;

    protected int | string | array $columnSpan = 1;

    public static function canView(): bool
    {
        if ((bool) config('board.dashboard.use_executive_widgets', false)) {
            return false;
        }

        return auth()->check()
            && auth()->user()?->can('view_reports');
    }

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.minutes.heading');
    }

    protected function getDescription(): ?string
    {
        return __('dashboard.widgets.period_caption');
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $user = auth()->user();
        if ($user === null) {
            return [];
        }

        $m = app(DashboardMetricsService::class)
            ->getMetrics($user, DashboardMetricsPeriod::ThisMonth)['minutes'];

        return [
            Stat::make(__('dashboard.widgets.minutes.total'), $m['total_minutes']),
            Stat::make(__('dashboard.widgets.minutes.pending_review'), $m['minutes_pending_review']),
            Stat::make(__('dashboard.widgets.minutes.approved'), $m['minutes_approved']),
        ];
    }
}
