<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);

        $middleware->alias([
            // Sanctum abilities for token-scoped access (API v1).
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return app(\App\Services\Api\ApiResponder::class)->validationFailed($e);
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return app(\App\Services\Api\ApiResponder::class)->unauthorized('unauthorized', __('auth.unauthenticated'));
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            // Mensagem consistente para 403; não vazar detalhes internos.
            return app(\App\Services\Api\ApiResponder::class)->forbidden('forbidden', 'Forbidden');
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $e->getStatusCode();
            $responder = app(\App\Services\Api\ApiResponder::class);

            return match ($status) {
                401 => $responder->unauthorized('unauthorized', __('auth.unauthenticated')),
                403 => $responder->forbidden('forbidden', 'Forbidden'),
                404 => $responder->notFound('not_found', 'Not found'),
                429 => $responder->rateLimited('rate_limited', 'Too many requests'),
                default => $responder->error('server_error', 'Server error', $status),
            };
        });
    })->create();
