<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Dashboard\Executive\Snapshot\ExecutiveDashboardSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ExecutiveDashboardSnapshot
 */
final class DashboardSnapshotApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ExecutiveDashboardSnapshot $dto */
        $dto = $this->resource;

        return $dto->toArray();
    }
}
