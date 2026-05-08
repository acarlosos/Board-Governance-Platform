<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\V1\Tasks\ListTasksAction;
use App\Actions\Api\V1\Tasks\ResolveVisibleTaskAction;
use App\Actions\Api\V1\Tasks\UpdateTaskApiAction;
use App\Actions\Tasks\AddTaskCommentAction;
use App\Actions\Tasks\CancelTaskAction;
use App\Actions\Tasks\CompleteTaskAction;
use App\Actions\Tasks\PersistTaskAction;
use App\Actions\Tasks\StartTaskAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tasks\CreateTaskCommentRequest;
use App\Http\Requests\Api\V1\Tasks\CreateTaskRequest;
use App\Http\Requests\Api\V1\Tasks\ListTasksRequest;
use App\Http\Requests\Api\V1\Tasks\UpdateTaskRequest;
use App\Http\Resources\Api\V1\TaskApiResource;
use App\Models\TaskComment;
use App\Models\Task;
use App\Models\User;
use App\Services\Api\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

final class TasksController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
    ) {}

    private const RATE_LIMIT_DECAY_SECONDS = 60;

    public function index(ListTasksRequest $request, ListTasksAction $action): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        Gate::forUser($actor)->authorize('viewAnyInApi', Task::class);

        $paginator = $action->execute($actor, $request->validated());

        return $this->responder->paginated($paginator, TaskApiResource::class);
    }

    public function store(CreateTaskRequest $request): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        $key = 'api:v1:tasks:store:user:'.$actor->getKey();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return $this->responder->rateLimited();
        }
        RateLimiter::hit($key, self::RATE_LIMIT_DECAY_SECONDS);

        Gate::forUser($actor)->authorize('create', Task::class);

        $data = $request->validated();
        $data['status'] = \App\Enums\TaskStatus::Pending->value;

        $task = app(PersistTaskAction::class)->create($actor, $data);

        return $this->responder->ok(new TaskApiResource($task), 201);
    }

    public function update(UpdateTaskRequest $request, ResolveVisibleTaskAction $resolve, UpdateTaskApiAction $action, int $id): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        $key = 'api:v1:tasks:update:user:'.$actor->getKey();
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return $this->responder->rateLimited();
        }
        RateLimiter::hit($key, self::RATE_LIMIT_DECAY_SECONDS);

        $task = $resolve->execute($actor, $id);
        if ($task === null) {
            return $this->responder->notFound();
        }

        Gate::forUser($actor)->authorize('update', $task);

        $updated = $action->execute($actor, $task, $request->validated());

        return $this->responder->ok(new TaskApiResource($updated), 200);
    }

    public function start(Request $request, ResolveVisibleTaskAction $resolve, int $id): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        $key = 'api:v1:tasks:start:user:'.$actor->getKey();
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return $this->responder->rateLimited();
        }
        RateLimiter::hit($key, self::RATE_LIMIT_DECAY_SECONDS);

        $task = $resolve->execute($actor, $id);
        if ($task === null) {
            return $this->responder->notFound();
        }

        Gate::forUser($actor)->authorize('update', $task);

        $updated = app(StartTaskAction::class)->start($actor, $task);

        return $this->responder->ok(new TaskApiResource($updated), 200);
    }

    public function complete(Request $request, ResolveVisibleTaskAction $resolve, int $id): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        $key = 'api:v1:tasks:complete:user:'.$actor->getKey();
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return $this->responder->rateLimited();
        }
        RateLimiter::hit($key, self::RATE_LIMIT_DECAY_SECONDS);

        $task = $resolve->execute($actor, $id);
        if ($task === null) {
            return $this->responder->notFound();
        }

        Gate::forUser($actor)->authorize('update', $task);

        $updated = app(CompleteTaskAction::class)->complete($actor, $task);

        return $this->responder->ok(new TaskApiResource($updated), 200);
    }

    public function cancel(Request $request, ResolveVisibleTaskAction $resolve, int $id): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        $key = 'api:v1:tasks:cancel:user:'.$actor->getKey();
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return $this->responder->rateLimited();
        }
        RateLimiter::hit($key, self::RATE_LIMIT_DECAY_SECONDS);

        $task = $resolve->execute($actor, $id);
        if ($task === null) {
            return $this->responder->notFound();
        }

        Gate::forUser($actor)->authorize('update', $task);

        $updated = app(CancelTaskAction::class)->cancel($actor, $task);

        return $this->responder->ok(new TaskApiResource($updated), 200);
    }

    public function addComment(CreateTaskCommentRequest $request, ResolveVisibleTaskAction $resolve, int $id): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        $key = 'api:v1:tasks:comments:user:'.$actor->getKey();
        if (RateLimiter::tooManyAttempts($key, 20)) {
            return $this->responder->rateLimited();
        }
        RateLimiter::hit($key, self::RATE_LIMIT_DECAY_SECONDS);

        $task = $resolve->execute($actor, $id);
        if ($task === null) {
            return $this->responder->notFound();
        }

        Gate::forUser($actor)->authorize('view', $task);
        Gate::forUser($actor)->authorize('create', TaskComment::class);

        $comment = app(AddTaskCommentAction::class)->add($actor, $task, (string) $request->validated()['comment']);

        return $this->responder->ok([
            'id' => $comment->id,
            'task_id' => $comment->task_id,
            'user_id' => $comment->user_id,
            'comment' => $comment->comment,
            'created_at' => optional($comment->created_at)?->toISOString(),
        ], 201);
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

