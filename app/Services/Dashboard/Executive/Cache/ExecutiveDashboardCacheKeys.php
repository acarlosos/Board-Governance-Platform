<?php

namespace App\Services\Dashboard\Executive\Cache;

use App\Enums\DashboardMetricsPeriod;

/**
 * Chaves canónicas L1 (`dashboard_metrics`) e L2 (`dashboard_snapshot` shared plain) do dashboard executivo.
 */
final class ExecutiveDashboardCacheKeys
{
    private function __construct() {}

    public static function l1Key(string $cacheSegment, DashboardMetricsPeriod $period): string
    {
        return 'dashboard_metrics:v1:'.$cacheSegment.':'.$period->value;
    }

    public static function l2Key(string $cacheSegment, DashboardMetricsPeriod $period): string
    {
        $version = (string) config('board.dashboard.snapshot_version', 'v1');

        return 'dashboard_snapshot:'.$version.':'.$cacheSegment.':'.$period->value.':shared:plain';
    }

    /**
     * Três períodos × (L1 + L2) = 6 chaves por segmento de tenancy.
     *
     * @return list<string>
     */
    public static function allKeysForSegment(string $cacheSegment): array
    {
        $keys = [];
        foreach (self::periods() as $p) {
            $keys[] = self::l1Key($cacheSegment, $p);
            $keys[] = self::l2Key($cacheSegment, $p);
        }

        return $keys;
    }

    /**
     * @return list<DashboardMetricsPeriod>
     */
    public static function periods(): array
    {
        return DashboardMetricsPeriod::filterOptions();
    }
}
