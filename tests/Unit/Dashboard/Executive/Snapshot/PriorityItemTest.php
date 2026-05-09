<?php

namespace Tests\Unit\Dashboard\Executive\Snapshot;

use App\Services\Dashboard\Executive\Snapshot\Enums\PriorityUrgency;
use App\Services\Dashboard\Executive\Snapshot\PriorityItem;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PriorityItemTest extends TestCase
{
    #[Test]
    public function test_resource_type_is_string_not_fqcn(): void
    {
        $item = new PriorityItem(
            resourceType: 'task',
            id: 7,
            title: 'X',
            urgency: PriorityUrgency::Normal,
            dueAt: null,
        );

        $this->assertSame('task', $item->resourceType);
        $this->assertStringNotContainsString('\\', $item->resourceType);

        $this->assertSame('task', $item->toArray()['resource_type']);
    }

    #[Test]
    public function test_urgency_is_enum_in_php_string_in_array(): void
    {
        $item = new PriorityItem(
            resourceType: 'vote',
            id: 22,
            title: 'Emitir',
            urgency: PriorityUrgency::DueToday,
            dueAt: CarbonImmutable::parse('2026-05-09T23:59:59Z'),
        );

        $this->assertSame(PriorityUrgency::DueToday, $item->urgency);
        $this->assertSame('due_today', $item->toArray()['urgency']);
    }
}
