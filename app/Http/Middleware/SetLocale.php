<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Define o locale da aplicação: preferência do utilizador autenticado, senão config/app.php.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('localization.supported', []);
        $default = config('app.locale', 'pt_BR');

        $locale = $default;

        if ($request->user() !== null && filled($request->user()->locale)) {
            $candidate = (string) $request->user()->locale;
            if (in_array($candidate, $supported, true)) {
                $locale = $candidate;
            }
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
