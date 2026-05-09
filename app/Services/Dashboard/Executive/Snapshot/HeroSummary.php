<?php

namespace App\Services\Dashboard\Executive\Snapshot;

use Carbon\CarbonImmutable;

final readonly class HeroSummary
{
    public function __construct(
        public int $tasksOverdue,
        public int $votesOpen,
        public int $signaturesPending,
        public ?CarbonImmutable $nextMeetingAt,
        public ?int $nextMeetingId,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tasks_overdue' => $this->tasksOverdue,
            'votes_open' => $this->votesOpen,
            'signatures_pending' => $this->signaturesPending,
            'next_meeting_at' => $this->nextMeetingAt?->toIso8601String(),
            'next_meeting_id' => $this->nextMeetingId,
        ];
    }
}
