<?php

namespace App\Services\Dashboard\Executive\Snapshot;

use Carbon\CarbonImmutable;

final readonly class ActivityItem
{
    public function __construct(
        public string $resourceType,
        public ?int $resourceId,
        public string $summary,
        public CarbonImmutable $occurredAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'resource_type' => $this->resourceType,
            'resource_id' => $this->resourceId,
            'summary' => $this->summary,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
