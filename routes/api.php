<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('login', [AuthController::class, 'login'])->name('api.v1.auth.login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');

            Route::get('me', [AuthController::class, 'me'])
                ->middleware('abilities:auth:read')
                ->name('api.v1.auth.me');

            Route::get('tokens', [AuthController::class, 'tokens'])
                ->middleware('abilities:tokens:read:self')
                ->name('api.v1.auth.tokens.index');

            Route::post('tokens', [AuthController::class, 'createToken'])
                ->middleware('abilities:tokens:manage:self')
                ->name('api.v1.auth.tokens.store');

            Route::delete('tokens/{token_id}', [AuthController::class, 'revokeToken'])
                ->middleware('abilities:tokens:manage:self')
                ->name('api.v1.auth.tokens.destroy');
        });
    });
});

