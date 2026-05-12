<?php

namespace App\Services\Dashboard\Executive\Observability;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Counters diários agregados (D13, D15) — sem tenant_id em chaves.
 *
 * @see docs/execution/19B.2-dashboard-observability.md
 */
final class ExecutiveDashboardObservability
{
    public const COUNTER_TTL_SECONDS = 60 * 60 * 24 * 7;

    public const PREFIX = 'dashboard:obs:';

    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    public function recordL1Hit(): void
    {
        $this->increment('l1:hit');
    }

    public function recordL1Miss(): void
    {
        $this->increment('l1:miss');
    }

    public function recordL2Hit(): void
    {
        $this->increment('l2:hit');
    }

    public function recordL2Miss(): void
    {
        $this->increment('l2:miss');
    }

    public function recordInvalidation(): void
    {
        $this->increment('invalidations');
    }

    /**
     * @return array{
     *     day: string,
     *     l1: array{hits: int, misses: int, hit_ratio: float},
     *     l2: array{hits: int, misses: int, hit_ratio: float},
     *     invalidations: int
     * }
     */
    public function snapshotFor(DateTimeInterface $day): array
    {
        $dayStr = CarbonImmutable::instance($day)
            ->setTimezone(config('app.timezone'))
            ->format('Y-m-d');

        $l1Hits = $this->counterValue('l1:hit', $dayStr);
        $l1Misses = $this->counterValue('l1:miss', $dayStr);
        $l2Hits = $this->counterValue('l2:hit', $dayStr);
        $l2Misses = $this->counterValue('l2:miss', $dayStr);
        $invalidations = $this->counterValue('invalidations', $dayStr);

        return [
            'day' => $dayStr,
            'l1' => [
                'hits' => $l1Hits,
                'misses' => $l1Misses,
                'hit_ratio' => $this->hitRatio($l1Hits, $l1Misses),
            ],
            'l2' => [
                'hits' => $l2Hits,
                'misses' => $l2Misses,
                'hit_ratio' => $this->hitRatio($l2Hits, $l2Misses),
            ],
            'invalidations' => $invalidations,
        ];
    }

    private function increment(string $bucket): void
    {
        $key = $this->counterKey($bucket, $this->todayDateString());
        $this->cache->add($key, 0, self::COUNTER_TTL_SECONDS);
        $this->cache->increment($key);
    }

    private function counterKey(string $bucket, string $dayStr): string
    {
        return self::PREFIX.$bucket.':'.$dayStr;
    }

    private function todayDateString(): string
    {
        return CarbonImmutable::now(config('app.timezone'))->format('Y-m-d');
    }

    private function counterValue(string $bucket, string $dayStr): int
    {
        $raw = $this->cache->get($this->counterKey($bucket, $dayStr), 0);

        return is_numeric($raw) ? (int) $raw : 0;
    }

    private function hitRatio(int $hits, int $misses): float
    {
        $den = max(1, $hits + $misses);

        return round($hits / $den, 3);
    }
}
