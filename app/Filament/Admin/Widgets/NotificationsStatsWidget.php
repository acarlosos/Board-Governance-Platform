<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\DashboardMetricsPeriod;
use App\Services\Dashboard\DashboardMetricsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NotificationsStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 25;

    protected int | string | array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->check()
            && auth()->user()?->can('view_reports');
    }

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.notifications.heading');
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
            ->getMetrics($user, DashboardMetricsPeriod::ThisMonth)['notifications'];

        return [
            Stat::make(__('dashboard.widgets.notifications.total'), $m['total_notifications']),
            Stat::make(__('dashboard.widgets.notifications.unread'), $m['unread_notifications']),
        ];
    }
}
