<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\V1\Tasks\ListTasksAction;
use App\Actions\Api\V1\Tasks\ResolveVisibleTaskAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tasks\ListTasksRequest;
use App\Http\Resources\Api\V1\TaskApiResource;
use App\Models\Task;
use App\Models\User;
use App\Services\Api\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class TasksController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
    ) {}

    public function index(ListTasksRequest $request, ListTasksAction $action): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        Gate::forUser($actor)->authorize('viewAny', Task::class);

        $paginator = $action->execute($actor, $request->validated());

        return $this->responder->paginated($paginator, TaskApiResource::class);
    }

    public function show(Request $request, ResolveVisibleTaskAction $action, int $id): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        $task = $action->execute($actor, $id);
        if ($task === null) {
            return $this->responder->notFound();
        }

        return $this->responder->ok(new TaskApiResource($task), 200);
    }
}

