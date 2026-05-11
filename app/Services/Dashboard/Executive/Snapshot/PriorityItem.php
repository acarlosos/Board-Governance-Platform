<?php

namespace App\Services\Dashboard\Executive\Snapshot;

use App\Services\Dashboard\Executive\Snapshot\Enums\PriorityUrgency;
use Carbon\CarbonImmutable;

final readonly class PriorityItem
{
    public function __construct(
        public string $resourceType,
        public int $id,
        public string $title,
        public PriorityUrgency $urgency,
        public ?CarbonImmutable $dueAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'resource_type' => $this->resourceType,
            'id' => $this->id,
            'title' => $this->title,
            'urgency' => $this->urgency->value,
            'due_at' => $this->dueAt?->toIso8601String(),
        ];
    }
}
