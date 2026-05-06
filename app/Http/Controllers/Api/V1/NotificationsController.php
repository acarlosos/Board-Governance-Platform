<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\V1\Notifications\ListNotificationsAction;
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

final class NotificationsController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
    ) {}

    public function index(ListNotificationsRequest $request, ListNotificationsAction $action): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        Gate::forUser($actor)->authorize('viewAny', NotificationCenter::class);

        $paginator = $action->execute($actor, $request->validated());

        return $this->responder->paginated($paginator, NotificationApiResource::class);
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

