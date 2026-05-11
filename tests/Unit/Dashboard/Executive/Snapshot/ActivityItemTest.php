<?php

namespace Tests\Unit\Dashboard\Executive\Snapshot;

use App\Services\Dashboard\Executive\Snapshot\ActivityItem;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ActivityItemTest extends TestCase
{
    #[Test]
    public function test_resource_id_can_be_null_for_orphan(): void
    {
        $item = new ActivityItem(
            resourceType: 'system',
            resourceId: null,
            summary: 'Event without linked row',
            occurredAt: CarbonImmutable::parse('2026-05-09T16:00:00Z'),
        );

        $this->assertNull($item->resourceId);

        $row = $item->toArray();
        $this->assertNull($row['resource_id']);
        $this->assertSame('system', $row['resource_type']);
    }
}
