<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\V1\Meetings\ListMeetingsAction;
use App\Actions\Api\V1\Meetings\ResolveVisibleMeetingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Meetings\ListMeetingsRequest;
use App\Http\Resources\Api\V1\MeetingApiResource;
use App\Models\Meeting;
use App\Models\User;
use App\Services\Api\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class MeetingsController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
    ) {}

    public function index(ListMeetingsRequest $request, ListMeetingsAction $action): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        Gate::forUser($actor)->authorize('viewAny', Meeting::class);

        $paginator = $action->execute($actor, $request->validated());

        return $this->responder->paginated($paginator, MeetingApiResource::class);
    }

    public function show(Request $request, ResolveVisibleMeetingAction $action, int $id): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        $meeting = $action->execute($actor, $id);
        if ($meeting === null) {
            return $this->responder->notFound();
        }

        return $this->responder->ok(new MeetingApiResource($meeting), 200);
    }
}

