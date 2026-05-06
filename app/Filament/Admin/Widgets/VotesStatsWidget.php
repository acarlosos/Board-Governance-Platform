<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\DashboardMetricsPeriod;
use App\Services\Dashboard\DashboardMetricsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VotesStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 23;

    protected int | string | array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->check()
            && auth()->user()?->can('view_reports');
    }

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.votes.heading');
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
            ->getMetrics($user, DashboardMetricsPeriod::ThisMonth)['votes'];

        return [
            Stat::make(__('dashboard.widgets.votes.total'), $m['total_votes']),
            Stat::make(__('dashboard.widgets.votes.open'), $m['votes_open']),
            Stat::make(__('dashboard.widgets.votes.closed'), $m['votes_closed']),
        ];
    }
}
