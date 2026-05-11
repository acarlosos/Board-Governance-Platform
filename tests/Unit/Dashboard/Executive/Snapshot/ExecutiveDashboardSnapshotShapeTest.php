<?php

namespace Tests\Unit\Dashboard\Executive\Snapshot;

use App\Enums\DashboardMetricsPeriod;
use App\Services\Dashboard\Executive\Snapshot\ExecutiveDashboardSnapshot;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExecutiveDashboardSnapshotShapeTest extends TestCase
{
    #[Test]
    public function test_empty_snapshot_has_complete_shape(): void
    {
        $generatedAt = CarbonImmutable::parse('2026-05-09T14:30:00Z');
        $snapshot = ExecutiveDashboardSnapshot::emptyShape(
            DashboardMetricsPeriod::ThisMonth,
            't_42',
            $generatedAt,
        );

        $data = $snapshot->toArray();

        $required = [
            'version',
            'period',
            'cache_segment',
            'generated_at',
            'hero',
            'kpis',
            'operations',
            'priorities',
            'activity',
        ];
        foreach ($required as $key) {
            $this->assertArrayHasKey($key, $data, "Missing shape key: {$key}");
        }

        foreach (['tasks_overdue', 'votes_open', 'signatures_pending', 'next_meeting_at', 'next_meeting_id'] as $k) {
            $this->assertArrayHasKey($k, $data['hero']);
        }
        foreach (['tasks', 'meetings', 'votes', 'signatures'] as $k) {
            $this->assertArrayHasKey($k, $data['kpis']);
        }
        foreach (['minutes_pending_review', 'meetings_this_month', 'notifications_unread'] as $k) {
            $this->assertArrayHasKey($k, $data['operations']);
        }

        $this->assertSame([], $data['priorities']);
        $this->assertSame([], $data['activity']);

        $this->assertSame(0, $data['operations']['notifications_unread']);
        $this->assertSame(['tasks' => [], 'meetings' => [], 'votes' => [], 'signatures' => []], $data['kpis']);
    }

    #[Test]
    public function test_version_field_matches_config(): void
    {
        $snapshot = ExecutiveDashboardSnapshot::emptyShape(
            DashboardMetricsPeriod::Last30Days,
            't_1',
            CarbonImmutable::now(),
        );

        $this->assertSame((string) config('board.dashboard.snapshot_version'), $snapshot->version);
        $this->assertSame((string) config('board.dashboard.snapshot_version'), $snapshot->toArray()['version']);
    }

    #[Test]
    public function test_super_admin_global_has_empty_feeds(): void
    {
        $snapshot = ExecutiveDashboardSnapshot::emptyShape(
            DashboardMetricsPeriod::AllTime,
            'global',
            CarbonImmutable::now(),
        );

        $data = $snapshot->toArray();
        $this->assertSame('global', $data['cache_segment']);
        $this->assertSame([], $data['priorities']);
        $this->assertSame([], $data['activity']);
    }
}
