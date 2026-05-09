<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BoardsController;
use App\Http\Controllers\Api\V1\MeetingsController;
use App\Http\Controllers\Api\V1\NotificationsController;
use App\Http\Controllers\Api\V1\TasksController;
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

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('boards', [BoardsController::class, 'index'])
            ->middleware('abilities:boards:read')
            ->name('api.v1.boards.index');
        Route::get('boards/{id}', [BoardsController::class, 'show'])
            ->middleware('abilities:boards:read')
            ->name('api.v1.boards.show');

        Route::get('meetings', [MeetingsController::class, 'index'])
            ->middleware('abilities:meetings:read')
            ->name('api.v1.meetings.index');
        Route::get('meetings/{id}', [MeetingsController::class, 'show'])
            ->middleware('abilities:meetings:read')
            ->name('api.v1.meetings.show');

        Route::get('tasks', [TasksController::class, 'index'])
            ->middleware('abilities:tasks:read')
            ->name('api.v1.tasks.index');
        Route::get('tasks/{id}', [TasksController::class, 'show'])
            ->middleware('abilities:tasks:read')
            ->name('api.v1.tasks.show')
            ->whereNumber('id');

        Route::post('tasks', [TasksController::class, 'store'])
            ->middleware('abilities:tasks:write')
            ->name('api.v1.tasks.store');
        Route::patch('tasks/{id}', [TasksController::class, 'update'])
            ->middleware('abilities:tasks:write')
            ->name('api.v1.tasks.update')
            ->whereNumber('id');
        Route::post('tasks/{id}/start', [TasksController::class, 'start'])
            ->middleware('abilities:tasks:write')
            ->name('api.v1.tasks.start')
            ->whereNumber('id');
        Route::post('tasks/{id}/complete', [TasksController::class, 'complete'])
            ->middleware('abilities:tasks:write')
            ->name('api.v1.tasks.complete')
            ->whereNumber('id');
        Route::post('tasks/{id}/cancel', [TasksController::class, 'cancel'])
            ->middleware('abilities:tasks:write')
            ->name('api.v1.tasks.cancel')
            ->whereNumber('id');
        Route::post('tasks/{id}/comments', [TasksController::class, 'addComment'])
            ->middleware('abilities:tasks:write')
            ->name('api.v1.tasks.comments.store')
            ->whereNumber('id');

        Route::get('notifications', [NotificationsController::class, 'index'])
            ->middleware('abilities:notifications:read')
            ->name('api.v1.notifications.index');
        Route::get('notifications/{id}', [NotificationsController::class, 'show'])
            ->middleware('abilities:notifications:read')
            ->name('api.v1.notifications.show')
            ->whereNumber('id');

        Route::post('notifications/read-all', [NotificationsController::class, 'readAll'])
            ->middleware('abilities:notifications:write')
            ->name('api.v1.notifications.read_all');
        Route::post('notifications/{id}/read', [NotificationsController::class, 'markRead'])
            ->middleware('abilities:notifications:write')
            ->name('api.v1.notifications.read')
            ->whereNumber('id');
    });
});

