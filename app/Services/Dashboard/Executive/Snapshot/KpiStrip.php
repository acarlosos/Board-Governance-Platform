<?php

namespace App\Services\Dashboard\Executive\Snapshot;

final readonly class KpiStrip
{
    /**
     * @param  array<string, int>  $tasks
     * @param  array<string, int>  $meetings
     * @param  array<string, int>  $votes
     * @param  array<string, int>  $signatures
     */
    public function __construct(
        public array $tasks,
        public array $meetings,
        public array $votes,
        public array $signatures,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tasks' => $this->tasks,
            'meetings' => $this->meetings,
            'votes' => $this->votes,
            'signatures' => $this->signatures,
        ];
    }
}
