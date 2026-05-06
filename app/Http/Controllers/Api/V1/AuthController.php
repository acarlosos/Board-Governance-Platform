<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\V1\CreateTokenAction;
use App\Actions\Api\V1\ListTokensAction;
use App\Actions\Api\V1\LoginAction;
use App\Actions\Api\V1\LogoutAction;
use App\Actions\Api\V1\MeAction;
use App\Actions\Api\V1\RevokeTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateTokenRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\MeResource;
use App\Http\Resources\Api\V1\TokenResource;
use App\Models\User;
use App\Services\Api\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
    ) {}

    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $payload = $action->execute($request->validated());

        return $this->responder->ok($payload, 200);
    }

    public function logout(Request $request, LogoutAction $action): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return $this->responder->unauthorized();
        }

        /** @var ?PersonalAccessToken $token */
        $token = $user->currentAccessToken();

        $action->execute($user, $token);

        return $this->responder->ok(['revoked' => true], 200);
    }

    public function me(Request $request, MeAction $action): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return $this->responder->unauthorized();
        }

        /** @var ?PersonalAccessToken $token */
        $token = $user->currentAccessToken();

        return $this->responder->ok(new MeResource($action->execute($user, $token)));
    }

    public function tokens(Request $request, ListTokensAction $action): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return $this->responder->unauthorized();
        }

        $perPage = (int) ($request->query('per_page', 15));
        $perPage = max(1, min(100, $perPage));

        $paginator = $action->execute($user, $perPage);

        return $this->responder->paginated($paginator, TokenResource::class);
    }

    public function createToken(CreateTokenRequest $request, CreateTokenAction $action): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return $this->responder->unauthorized();
        }

        $payload = $action->execute($user, $request->validated());

        return $this->responder->ok($payload, 201);
    }

    public function revokeToken(Request $request, RevokeTokenAction $action, int $token_id): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return $this->responder->unauthorized();
        }

        $ok = $action->execute($user, $token_id);

        if (! $ok) {
            return $this->responder->notFound();
        }

        return $this->responder->ok(['revoked' => true], 200);
    }
}

