<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\V1\Boards\ListBoardsAction;
use App\Actions\Api\V1\Boards\ResolveVisibleBoardAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Boards\ListBoardsRequest;
use App\Http\Resources\Api\V1\BoardApiResource;
use App\Models\Board;
use App\Models\User;
use App\Services\Api\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class BoardsController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
    ) {}

    public function index(ListBoardsRequest $request, ListBoardsAction $action): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        Gate::forUser($actor)->authorize('viewAnyInApi', Board::class);

        $paginator = $action->execute($actor, $request->validated());

        return $this->responder->paginated($paginator, BoardApiResource::class);
    }

    public function show(Request $request, ResolveVisibleBoardAction $action, int $id): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized();
        }

        $board = $action->execute($actor, $id);
        if ($board === null) {
            return $this->responder->notFound();
        }

        return $this->responder->ok(new BoardApiResource($board), 200);
    }
}

