<?php

namespace App\Services\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;
use Illuminate\Validation\ValidationException;

final class ApiResponder
{
    public function __construct(
        private readonly RequestIdService $requestId,
    ) {}

    /**
     * @param  array<string, mixed>|JsonResource|list<mixed>  $data
     */
    public function ok(array|JsonResource $data, int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data instanceof JsonResource ? $data->resolve(request()) : $data,
            'meta' => $this->meta($meta),
        ], $status);
    }

    /**
     * @param  class-string<JsonResource>  $resourceClass
     */
    public function paginated(ConcreteLengthAwarePaginator $paginator, string $resourceClass, array $meta = []): JsonResponse
    {
        $items = $paginator->getCollection();

        return response()->json([
            'success' => true,
            'data' => $resourceClass::collection($items)->resolve(request()),
            'meta' => $this->meta(array_merge($meta, [
                'pagination' => [
                    'page' => (int) $paginator->currentPage(),
                    'per_page' => (int) $paginator->perPage(),
                    'total' => (int) $paginator->total(),
                ],
            ])),
        ]);
    }

    public function error(string $code, string $message, int $status = 500, array $fields = []): JsonResponse
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($fields !== []) {
            $error['fields'] = $fields;
        }

        return response()->json([
            'success' => false,
            'error' => $error,
            'meta' => $this->meta(),
        ], $status);
    }

    public function validationFailed(ValidationException $e): JsonResponse
    {
        return $this->error(
            code: 'validation_failed',
            message: 'Validation failed',
            status: 422,
            fields: $e->errors(),
        );
    }

    public function unauthorized(string $code = 'unauthorized', string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($code, $message, 401);
    }

    public function forbidden(string $code = 'forbidden', string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($code, $message, 403);
    }

    public function notFound(string $code = 'not_found', string $message = 'Not found'): JsonResponse
    {
        return $this->error($code, $message, 404);
    }

    public function rateLimited(string $code = 'rate_limited', string $message = 'Too many requests'): JsonResponse
    {
        return $this->error($code, $message, 429);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function meta(array $extra = []): array
    {
        return array_merge([
            'request_id' => $this->requestId->get(),
            'api_version' => 'v1',
        ], $extra);
    }
}

