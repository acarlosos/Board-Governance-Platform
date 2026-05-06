<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Task */
final class TaskApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'tenant_id' => $this->tenant_id,
            'title' => (string) $this->title,
            'description' => $this->description,
            'status' => (string) $this->status->value,
            'priority' => (string) $this->priority->value,
            'due_date' => optional($this->due_date)->toISOString(),
            'assigned_to' => $this->assigned_to,
            'created_by' => $this->created_by,
            'completed_at' => optional($this->completed_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}

