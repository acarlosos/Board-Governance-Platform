<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\V1\Notifications\ListNotificationsAction;
use App\Actions\Api\V1\Notifications\MarkAllNotificationsAsReadApiAction;
use App\Actions\Api\V1\Notifications\ResolveVisibleNotificationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notifications\ListNotificationsRequest;
use App\Http\Resources\Api\V1\NotificationApiResource;
use App\Models\NotificationCenter;
use App\Models\User;
use App\Services\Api\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

final class NotificationsController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
    ) {}

    private const RATE_LIMIT_DECAY_SECONDS = 60;

    public function index(ListNotificationsRequest $request, ListNotificationsAction $action): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        Gate::forUser($actor)->authorize('viewAnyInApi', NotificationCenter::class);

        $paginator = $action->execute($actor, $request->validated());

        return $this->responder->paginated($paginator, NotificationApiResource::class);
    }

    public function markRead(Request $request, ResolveVisibleNotificationAction $resolve, int $id): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        $key = 'api:v1:notifications:read:user:'.$actor->getKey();
        if (RateLimiter::tooManyAttempts($key, 120)) {
            return $this->responder->rateLimited();
        }
        RateLimiter::hit($key, self::RATE_LIMIT_DECAY_SECONDS);

        $notification = $resolve->execute($actor, $id);
        if ($notification === null) {
            return $this->responder->notFound();
        }

        Gate::forUser($actor)->authorize('markAsRead', $notification);

        $updated = app(\App\Actions\Notifications\MarkNotificationAsReadAction::class)->mark($actor, $notification);

        return $this->responder->ok(new NotificationApiResource($updated), 200);
    }

    public function readAll(Request $request, MarkAllNotificationsAsReadApiAction $action): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        $key = 'api:v1:notifications:read_all:user:'.$actor->getKey();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return $this->responder->rateLimited();
        }
        RateLimiter::hit($key, self::RATE_LIMIT_DECAY_SECONDS);

        Gate::forUser($actor)->authorize('viewAnyInApi', NotificationCenter::class);

        return $this->responder->ok($action->execute($actor), 200);
    }

    public function show(Request $request, ResolveVisibleNotificationAction $action, int $id): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        $notification = $action->execute($actor, $id);
        if ($notification === null) {
            return $this->responder->notFound();
        }

        return $this->responder->ok(new NotificationApiResource($notification), 200);
    }
}

