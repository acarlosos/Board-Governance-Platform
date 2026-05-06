<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\DashboardMetricsPeriod;
use App\Services\Dashboard\DashboardMetricsService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TasksStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 20;

    protected int | string | array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->check()
            && auth()->user()?->can('view_reports');
    }

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.tasks.heading');
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
            ->getMetrics($user, DashboardMetricsPeriod::ThisMonth)['tasks'];

        return [
            Stat::make(__('dashboard.widgets.tasks.total'), $m['total_tasks']),
            Stat::make(__('dashboard.widgets.tasks.open'), $m['tasks_open']),
            Stat::make(__('dashboard.widgets.tasks.completed'), $m['tasks_completed']),
            Stat::make(__('dashboard.widgets.tasks.overdue'), $m['tasks_overdue'])
                ->icon(Heroicon::OutlinedExclamationTriangle),
        ];
    }
}
