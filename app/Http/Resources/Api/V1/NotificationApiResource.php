<?php

namespace App\Http\Resources\Api\V1;

use App\Models\NotificationCenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NotificationCenter */
final class NotificationApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'title' => (string) $this->title,
            'body' => (string) $this->body,
            'channel' => (string) $this->channel->value,
            'status' => (string) $this->status->value,
            'read_at' => optional($this->read_at)->toISOString(),
            'sent_at' => optional($this->sent_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}

