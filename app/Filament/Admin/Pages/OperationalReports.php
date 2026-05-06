<?php

namespace App\Filament\Admin\Pages;

use App\Enums\DashboardMetricsPeriod;
use App\Models\User;
use App\Services\Reports\ReportsService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class OperationalReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 47;

    protected string $view = 'filament.admin.pages.operational-reports';

    public string $period = '';

    public function mount(): void
    {
        if ($this->period === '') {
            $this->period = DashboardMetricsPeriod::ThisMonth->value;
        }
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->can('view_reports');
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (! auth()->hasUser()) {
            return false;
        }

        return static::canAccess();
    }

    public function getHeading(): string|Htmlable|null
    {
        return __('reports.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('reports.navigation_label');
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return __('reports.navigation_group');
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function reportSummary(): array
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return [];
        }

        $period = DashboardMetricsPeriod::tryFrom($this->period)
            ?? DashboardMetricsPeriod::ThisMonth;

        return app(ReportsService::class)->summary($user, $period);
    }
}
