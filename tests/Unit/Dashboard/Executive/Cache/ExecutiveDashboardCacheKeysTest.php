<?php

namespace Tests\Unit\Dashboard\Executive\Cache;

use App\Enums\DashboardMetricsPeriod;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheKeys;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExecutiveDashboardCacheKeysTest extends TestCase
{
    #[Test]
    public function test_l1_key_formato_canonico(): void
    {
        $this->assertSame(
            'dashboard_metrics:v1:t_42:this_month',
            ExecutiveDashboardCacheKeys::l1Key('t_42', DashboardMetricsPeriod::ThisMonth),
        );
    }

    #[Test]
    public function test_l2_key_formato_canonico(): void
    {
        config(['board.dashboard.snapshot_version' => 'v1']);

        $this->assertSame(
            'dashboard_snapshot:v1:t_42:this_month:shared:plain',
            ExecutiveDashboardCacheKeys::l2Key('t_42', DashboardMetricsPeriod::ThisMonth),
        );
    }

    #[Test]
    public function test_all_keys_for_segment_devolve_seis_chaves_l1_e_l2_por_periodo(): void
    {
        config(['board.dashboard.snapshot_version' => 'v1']);

        $keys = ExecutiveDashboardCacheKeys::allKeysForSegment('t_99');

        $this->assertCount(6, $keys);
        $this->assertSame(
            [
                'dashboard_metrics:v1:t_99:this_month',
                'dashboard_snapshot:v1:t_99:this_month:shared:plain',
                'dashboard_metrics:v1:t_99:last_30_days',
                'dashboard_snapshot:v1:t_99:last_30_days:shared:plain',
                'dashboard_metrics:v1:t_99:all_time',
                'dashboard_snapshot:v1:t_99:all_time:shared:plain',
            ],
            $keys,
        );
    }

    #[Test]
    public function test_periods_delega_a_enum_filter_options(): void
    {
        $this->assertSame(
            DashboardMetricsPeriod::filterOptions(),
            ExecutiveDashboardCacheKeys::periods(),
        );
    }
}
