<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\DashboardMetricsPeriod;
use App\Services\Dashboard\DashboardMetricsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MeetingsStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 21;

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
        return __('dashboard.widgets.meetings.heading');
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
            ->getMetrics($user, DashboardMetricsPeriod::ThisMonth)['meetings'];

        return [
            Stat::make(__('dashboard.widgets.meetings.total'), $m['total_meetings']),
            Stat::make(__('dashboard.widgets.meetings.this_month'), $m['meetings_this_month']),
            Stat::make(__('dashboard.widgets.meetings.completed'), $m['meetings_completed']),
        ];
    }
}
