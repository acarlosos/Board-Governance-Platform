<?php

use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\SetLocale;
use App\Services\Api\ApiResponder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('dashboard:refresh-projections')
            ->everyFiveMinutes()
            ->withoutOverlapping(10)
            ->runInBackground();

        $schedule->command('backup:run')
            ->dailyAt('03:00')
            ->withoutOverlapping(60)
            ->onOneServer()
            ->runInBackground();

        $schedule->command('backup:clean')
            ->dailyAt('03:30')
            ->withoutOverlapping(60)
            ->onOneServer()
            ->runInBackground();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            SecurityHeadersMiddleware::class,
        ]);

        $middleware->alias([
            // Sanctum abilities for token-scoped access (API v1).
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return app(ApiResponder::class)->validationFailed($e);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return app(ApiResponder::class)->unauthorized('unauthenticated', __('auth.unauthenticated'));
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            // Na maior parte dos fluxos HTTP, `prepareException` já transformou isto
            // em `AccessDeniedHttpException` — ver handler `HttpExceptionInterface`.
            if ($e instanceof MissingAbilityException) {
                return app(ApiResponder::class)->forbidden('forbidden_ability', 'Forbidden');
            }

            return app(ApiResponder::class)->forbidden('forbidden_policy', 'Forbidden');
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $e->getStatusCode();
            $responder = app(ApiResponder::class);

            if ($status === 403) {
                $previous = $e->getPrevious();
                if ($previous instanceof MissingAbilityException) {
                    return $responder->forbidden('forbidden_ability', 'Forbidden');
                }
                if ($previous instanceof AuthorizationException) {
                    return $responder->forbidden('forbidden_policy', 'Forbidden');
                }
            }

            return match ($status) {
                401 => $responder->unauthorized('unauthorized', __('auth.unauthenticated')),
                403 => $responder->forbidden('forbidden', 'Forbidden'),
                404 => $responder->notFound('not_found', 'Not found'),
                429 => $responder->rateLimited('rate_limited', 'Too many requests'),
                default => $responder->error('server_error', 'Server error', $status),
            };
        });
    })->create();
