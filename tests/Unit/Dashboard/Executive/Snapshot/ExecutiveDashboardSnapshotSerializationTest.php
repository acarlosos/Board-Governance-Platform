<?php

namespace Tests\Unit\Dashboard\Executive\Snapshot;

use App\Enums\DashboardMetricsPeriod;
use App\Services\Dashboard\Executive\Snapshot\ActivityItem;
use App\Services\Dashboard\Executive\Snapshot\Enums\PriorityUrgency;
use App\Services\Dashboard\Executive\Snapshot\ExecutiveDashboardSnapshot;
use App\Services\Dashboard\Executive\Snapshot\HeroSummary;
use App\Services\Dashboard\Executive\Snapshot\KpiStrip;
use App\Services\Dashboard\Executive\Snapshot\OperationsBlock;
use App\Services\Dashboard\Executive\Snapshot\PriorityItem;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExecutiveDashboardSnapshotSerializationTest extends TestCase
{
    private function snapshotWithSampleData(): ExecutiveDashboardSnapshot
    {
        $generated = CarbonImmutable::parse('2026-05-09T15:00:00Z');

        return new ExecutiveDashboardSnapshot(
            version: (string) config('board.dashboard.snapshot_version'),
            period: DashboardMetricsPeriod::ThisMonth,
            cacheSegment: 't_99',
            generatedAt: $generated,
            hero: new HeroSummary(1, 2, 3, CarbonImmutable::parse('2026-05-10T10:00:00Z'), 500),
            kpis: new KpiStrip(
                ['total' => 10, 'open' => 4],
                ['scheduled' => 2],
                ['open' => 1],
                ['pending' => 3],
            ),
            operations: new OperationsBlock(5, 6, 7),
            priorities: [
                new PriorityItem('task', 1, 'Cortar relva', PriorityUrgency::Overdue, $generated),
            ],
            activity: [
                new ActivityItem('minute', null, 'Aprovada', CarbonImmutable::parse('2026-05-09T14:59:59Z')),
            ],
        );
    }

    #[Test]
    public function test_serialize_unserialize_round_trip(): void
    {
        $snapshot = $this->snapshotWithSampleData();
        $serialized = serialize($snapshot);
        $snapshot2 = unserialize($serialized);

        $this->assertInstanceOf(ExecutiveDashboardSnapshot::class, $snapshot2);
        $this->assertSame($snapshot->toArray(), $snapshot2->toArray());
    }

    #[Test]
    public function test_to_array_is_json_encodable_without_loss(): void
    {
        $snapshot = $this->snapshotWithSampleData();
        $arr = $snapshot->toArray();

        $json = json_encode($arr, JSON_THROW_ON_ERROR);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($arr, $decoded);
    }

    #[Test]
    public function test_to_array_keys_are_snake_case(): void
    {
        $snapshot = $this->snapshotWithSampleData();

        $this->assertRecursiveSnakeCaseKeys($snapshot->toArray());
    }

    /**
     * @param  array<mixed>  $data
     */
    private function assertRecursiveSnakeCaseKeys(array $data): void
    {
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $this->assertMatchesRegularExpression(
                    '/^[a-z][a-z0-9_]*$/',
                    $key,
                    "Key «{$key}» must be snake_case"
                );
            }
            if (is_array($value)) {
                $this->assertRecursiveSnakeCaseKeys($value);
            }
        }
    }
}
