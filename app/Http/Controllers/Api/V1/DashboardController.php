<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DashboardMetricsPeriod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Dashboard\SnapshotIndexRequest;
use App\Http\Resources\Api\V1\DashboardSnapshotApiResource;
use App\Models\User;
use App\Services\Api\ApiResponder;
use App\Services\Dashboard\Executive\ExecutiveDashboardReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
    ) {}

    public function snapshot(
        SnapshotIndexRequest $request,
        ExecutiveDashboardReadService $readService,
    ): JsonResponse {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->responder->unauthorized('unauthenticated', __('auth.unauthenticated'));
        }

        $key = 'api:v1:dashboard:snapshot:user:'.$actor->getKey();
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return $this->responder->rateLimited();
        }
        RateLimiter::hit($key, 60);

        Gate::forUser($actor)->authorize('view_executive_dashboard');

        $periodRaw = $request->validated('period');
        $periodEnum = match (true) {
            $periodRaw instanceof DashboardMetricsPeriod => $periodRaw,
            is_string($periodRaw) && $periodRaw !== '' => DashboardMetricsPeriod::from($periodRaw),
            default => DashboardMetricsPeriod::ThisMonth,
        };

        $snapshot = $readService->read($actor, $periodEnum);

        return $this->responder->ok(new DashboardSnapshotApiResource($snapshot), 200);
    }
}
