<?php

namespace App\Services\Dashboard\Executive;

use App\Enums\DashboardMetricsPeriod;
use App\Models\User;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheKeys;
use App\Services\Dashboard\Executive\Observability\ExecutiveDashboardObservability;
use App\Services\Dashboard\Executive\Projection\DashboardProjectionService;
use App\Services\Dashboard\Executive\Providers\ActivityFeedProvider;
use App\Services\Dashboard\Executive\Providers\HeroProvider;
use App\Services\Dashboard\Executive\Providers\KpiStripProvider;
use App\Services\Dashboard\Executive\Providers\OperationsProvider;
use App\Services\Dashboard\Executive\Providers\PrioritiesProvider;
use App\Services\Dashboard\Executive\Snapshot\ExecutiveDashboardSnapshot;
use App\Services\Dashboard\Executive\Snapshot\HeroSummary;
use App\Services\Dashboard\Executive\Snapshot\OperationsBlock;
use App\Services\Reporting\ReportingContext;
use Carbon\CarbonImmutable;
use Illuminate\Cache\Repository as CacheRepository;

final class ExecutiveDashboardReadService
{
    public function __construct(
        private readonly HeroProvider $hero,
        private readonly KpiStripProvider $kpi,
        private readonly OperationsProvider $operations,
        private readonly PrioritiesProvider $priorities,
        private readonly ActivityFeedProvider $activity,
        private readonly CacheRepository $cache,
        private readonly ExecutiveDashboardObservability $observability,
        private readonly DashboardProjectionService $projection,
    ) {}

    public function read(
        User $actor,
        DashboardMetricsPeriod $period = DashboardMetricsPeriod::ThisMonth,
    ): ExecutiveDashboardSnapshot {
        $ctx = ReportingContext::fromUser($actor);

        $kpis = $this->kpi->build($actor, $period);
        $shared = $this->loadOrComputeShared($actor, $ctx, $period);
        $priorities = $this->priorities->build($actor, $period);
        $activityFeed = $this->activity->build($actor, $period);

        return new ExecutiveDashboardSnapshot(
            version: (string) config('board.dashboard.snapshot_version', 'v1'),
            period: $period,
            cacheSegment: $ctx->cacheSegment(),
            generatedAt: CarbonImmutable::now(),
            hero: $shared['hero'],
            kpis: $kpis,
            operations: $shared['operations'],
            priorities: $priorities,
            activity: $activityFeed,
        );
    }

    /**
     * @return array{hero: HeroSummary, operations: OperationsBlock}
     */
    private function loadOrComputeShared(
        User $actor,
        ReportingContext $ctx,
        DashboardMetricsPeriod $period,
    ): array {
        if ($ctx->cacheSegment() === 'none') {
            return $this->hydrateSharedFromPlain($this->buildSharedPlain($actor, $period));
        }

        if (
            (bool) config('board.dashboard.use_projection', false)
            && ! $ctx->isGlobalScope()
            && $ctx->tenantId() !== null
        ) {
            $valid = $this->projection->findValid((int) $ctx->tenantId(), $period);
            if ($valid !== null) {
                $plain = $valid->payload;

                return $this->hydrateSharedFromPlain([
                    'hero' => is_array($plain) ? ($plain['hero'] ?? []) : [],
                    'operations' => is_array($plain) ? ($plain['operations'] ?? []) : [],
                ]);
            }
        }

        $l2Key = $this->sharedKey($ctx, $period);
        if ($this->cache->has($l2Key)) {
            $this->observability->recordL2Hit();
        } else {
            $this->observability->recordL2Miss();
        }

        /** @var array{hero: array<string, mixed>, operations: array<string, mixed>} $plain */
        $plain = $this->cache->flexible(
            $l2Key,
            [
                (int) config('board.dashboard.cache_stale_seconds', 60),
                (int) config('board.dashboard.cache_expire_seconds', 120),
            ],
            fn (): array => $this->buildSharedPlain($actor, $period),
        );

        return $this->hydrateSharedFromPlain($plain);
    }

    /**
     * Payload L2 apenas com escalares / arrays (nunca objectos DTO) — evita __PHP_Incomplete_Class ao unserialize.
     *
     * @return array{hero: array<string, mixed>, operations: array<string, mixed>}
     */
    private function buildSharedPlain(User $actor, DashboardMetricsPeriod $period): array
    {
        return [
            'hero' => $this->hero->build($actor, $period)->toArray(),
            'operations' => $this->operations->build($actor, $period)->toArray(),
        ];
    }

    /**
     * @param  array{hero: mixed, operations: mixed}  $plain
     * @return array{hero: HeroSummary, operations: OperationsBlock}
     */
    private function hydrateSharedFromPlain(array $plain): array
    {
        $heroRaw = $plain['hero'] ?? [];
        $opsRaw = $plain['operations'] ?? [];

        if (! is_array($heroRaw)) {
            $heroRaw = [];
        }
        if (! is_array($opsRaw)) {
            $opsRaw = [];
        }

        return [
            'hero' => HeroSummary::fromArray($heroRaw),
            'operations' => OperationsBlock::fromArray($opsRaw),
        ];
    }

    private function sharedKey(ReportingContext $ctx, DashboardMetricsPeriod $period): string
    {
        return ExecutiveDashboardCacheKeys::l2Key($ctx->cacheSegment(), $period);
    }
}
