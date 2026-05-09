<?php

namespace App\Services\Dashboard\Executive\Snapshot;

use App\Enums\DashboardMetricsPeriod;
use Carbon\CarbonImmutable;

final readonly class ExecutiveDashboardSnapshot
{
    /**
     * @param  array<int, PriorityItem>  $priorities
     * @param  array<int, ActivityItem>  $activity
     */
    public function __construct(
        public string $version,
        public DashboardMetricsPeriod $period,
        public string $cacheSegment,
        public CarbonImmutable $generatedAt,
        public HeroSummary $hero,
        public KpiStrip $kpis,
        public OperationsBlock $operations,
        public array $priorities,
        public array $activity,
    ) {}

    /**
     * Snapshot completo com contadores zerados e feeds vazios (shape estável para tenant vazio / cold start na UI).
     */
    public static function emptyShape(
        DashboardMetricsPeriod $period,
        string $cacheSegment,
        CarbonImmutable $generatedAt,
    ): self {
        $version = (string) config('board.dashboard.snapshot_version', 'v1');

        return new self(
            version: $version,
            period: $period,
            cacheSegment: $cacheSegment,
            generatedAt: $generatedAt,
            hero: new HeroSummary(
                tasksOverdue: 0,
                votesOpen: 0,
                signaturesPending: 0,
                nextMeetingAt: null,
                nextMeetingId: null,
            ),
            kpis: new KpiStrip(
                tasks: [],
                meetings: [],
                votes: [],
                signatures: [],
            ),
            operations: new OperationsBlock(
                minutesPendingReview: 0,
                meetingsThisMonth: 0,
                notificationsUnread: 0,
            ),
            priorities: [],
            activity: [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'period' => $this->period->value,
            'cache_segment' => $this->cacheSegment,
            'generated_at' => $this->generatedAt->toIso8601String(),
            'hero' => $this->hero->toArray(),
            'kpis' => $this->kpis->toArray(),
            'operations' => $this->operations->toArray(),
            'priorities' => array_map(
                fn (PriorityItem $item): array => $item->toArray(),
                $this->priorities,
            ),
            'activity' => array_map(
                fn (ActivityItem $item): array => $item->toArray(),
                $this->activity,
            ),
        ];
    }
}
