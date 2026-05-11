<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Widgets\Executive\ExecutiveHeroWidget;
use App\Filament\Admin\Widgets\Executive\ExecutiveKpiStripWidget;
use App\Filament\Admin\Widgets\Executive\ExecutiveOperationsWidget;
use App\Filament\Admin\Widgets\Executive\ExecutivePrioritiesWidget;
use App\Filament\Admin\Widgets\MeetingsStatsWidget;
use App\Filament\Admin\Widgets\MinutesStatsWidget;
use App\Filament\Admin\Widgets\NotificationsStatsWidget;
use App\Filament\Admin\Widgets\SignaturesStatsWidget;
use App\Filament\Admin\Widgets\TasksStatsWidget;
use App\Filament\Admin\Widgets\VotesStatsWidget;
use App\Filament\Admin\Pages\Auth\PgTrustLogin;
use App\Filament\Admin\Pages\Auth\PgTrustRequestPasswordReset;
use App\Filament\Admin\Pages\Auth\PgTrustResetPassword;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TouchAuthSessionActivity;
use Filament\Actions\EditAction;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(PgTrustLogin::class)
            ->passwordReset(PgTrustRequestPasswordReset::class, PgTrustResetPassword::class)
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable(),
            ])
            ->colors([
                'primary' => [
                    50 => '#fff1f3',
                    100 => '#ffe4e8',
                    200 => '#fecdd3',
                    300 => '#fda4af',
                    400 => '#fb7185',
                    500 => '#d71b3b',
                    600 => '#8b0a1f',
                    700 => '#6f0017',
                    800 => '#520012',
                    900 => '#3a000d',
                    950 => '#240008',
                ],
            ])
            // CSS locais servidos directamente de `public/`. `$path` é obrigatório para
            // que `php artisan filament:assets` (post-autoload-dump) não crashe ao chamar
            // `copyAsset(string $from, string $to)` — apontamos para o ficheiro já presente
            // em `public/`, resultando num copy-to-self (no-op seguro) em deploy.
            ->assets([
                Css::make('bgp-panel', public_path('css/app/bgp-panel.css'))
                    ->relativePublicPath('css/app/bgp-panel.css'),
                Css::make('bgp-login', public_path('css/app/bgp-login.css'))
                    ->relativePublicPath('css/app/bgp-login.css'),
                Css::make('bgp-dashboard', public_path('css/app/bgp-dashboard.css'))
                    ->relativePublicPath('css/app/bgp-dashboard.css'),
            ], package: 'app')
            ->bootUsing(function (): void {
                EditAction::configureUsing(
                    fn (EditAction $action): EditAction => $action->color('gray'),
                );
            })
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->widgets([
                // Executivos (Fase 19A.7) — sort 10-13. Visíveis quando
                // config('board.dashboard.use_executive_widgets') === true.
                ExecutiveHeroWidget::class,
                ExecutiveKpiStripWidget::class,
                ExecutiveOperationsWidget::class,
                ExecutivePrioritiesWidget::class,

                // Legados (Fase 14) — sort 20-25. Visíveis quando flag === false.
                // Remoção planeada para 19B.5 (D10).
                TasksStatsWidget::class,
                MeetingsStatsWidget::class,
                MinutesStatsWidget::class,
                VotesStatsWidget::class,
                SignaturesStatsWidget::class,
                NotificationsStatsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                SetLocale::class,
                SecurityHeadersMiddleware::class,
                TouchAuthSessionActivity::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
