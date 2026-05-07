<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Meeting */
final class MeetingApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'tenant_id' => $this->tenant_id,
            'board_id' => $this->board_id,
            'title' => (string) $this->title,
            'description' => $this->description,
            'scheduled_at' => optional($this->scheduled_at)->toISOString(),
            'starts_at' => optional($this->starts_at)->toISOString(),
            'ends_at' => optional($this->ends_at)->toISOString(),
            'video_conference_url' => $this->video_conference_url,
            'status' => (string) $this->status->value,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}

