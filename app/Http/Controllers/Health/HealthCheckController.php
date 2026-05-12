<?php

namespace App\Http\Controllers\Health;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

final class HealthCheckController
{
    public function __invoke(): JsonResponse
    {
        $dbOk = false;
        $cacheOk = false;

        try {
            DB::connection()->getPdo();
            $dbOk = true;
        } catch (Throwable) {
            // omitir detalhe — health público
        }

        try {
            Cache::put('health_probe', '1', 5);
            $cacheOk = Cache::get('health_probe') === '1';
        } catch (Throwable) {
            // omitir detalhe
        }

        $ok = $dbOk && $cacheOk;
        $payload = [
            'status' => $ok ? 'ok' : 'degraded',
            'db' => $dbOk ? 'ok' : 'fail',
            'cache' => $cacheOk ? 'ok' : 'fail',
            'app_env' => (string) config('app.env'),
        ];

        return response()->json($payload, $ok ? 200 : 503);
    }
}
