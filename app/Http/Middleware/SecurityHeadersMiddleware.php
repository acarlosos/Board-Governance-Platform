<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // X-Frame-Options
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // MIME sniffing protection
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (básica e conservadora)
        $response->headers->set(
            'Permissions-Policy',
            implode(', ', [
                'accelerometer=()',
                'autoplay=()',
                'camera=()',
                'clipboard-read=()',
                'clipboard-write=(self)',
                'encrypted-media=()',
                'fullscreen=(self)',
                'geolocation=()',
                'gyroscope=()',
                'magnetometer=()',
                'microphone=()',
                'midi=()',
                'payment=()',
                'picture-in-picture=(self)',
                'publickey-credentials-get=()',
                'screen-wake-lock=()',
                'sync-xhr=()',
                'usb=()',
                'web-share=()',
            ]),
        );

        // CSP (REPORT-ONLY por padrão para não quebrar Filament/Vite)
        $cspDirectives = [
            "default-src 'self'",
            // Filament usa inline styles em alguns componentes; começamos em report-only.
            "style-src 'self' 'unsafe-inline'",
            // Livewire/Filament pode usar inline scripts; começamos em report-only.
            "script-src 'self' 'unsafe-inline'",
            // Permite data: para QR code inline (2FA) e assets inline.
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            'object-src \'none\'',
        ];

        $response->headers->set('Content-Security-Policy-Report-Only', implode('; ', $cspDirectives));

        return $response;
    }
}

