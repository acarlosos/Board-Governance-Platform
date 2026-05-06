<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as FilamentDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends FilamentDashboard
{
    protected static ?string $title = null;

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
