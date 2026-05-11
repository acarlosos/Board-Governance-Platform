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
     * Reidrata a partir do output de {@see self::toArray()} (seguro para cache L2 sem serializar objectos PHP).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $nextAt = $data['next_meeting_at'] ?? null;

        return new self(
            tasksOverdue: (int) ($data['tasks_overdue'] ?? 0),
            votesOpen: (int) ($data['votes_open'] ?? 0),
            signaturesPending: (int) ($data['signatures_pending'] ?? 0),
            nextMeetingAt: is_string($nextAt) && $nextAt !== ''
                ? CarbonImmutable::parse($nextAt)
                : null,
            nextMeetingId: isset($data['next_meeting_id']) && $data['next_meeting_id'] !== null
                ? (int) $data['next_meeting_id']
                : null,
        );
    }

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
