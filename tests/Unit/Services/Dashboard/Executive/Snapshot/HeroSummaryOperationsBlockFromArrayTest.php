<?php

namespace Tests\Unit\Services\Dashboard\Executive\Snapshot;

use App\Services\Dashboard\Executive\Snapshot\HeroSummary;
use App\Services\Dashboard\Executive\Snapshot\OperationsBlock;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class HeroSummaryOperationsBlockFromArrayTest extends TestCase
{
    #[Test]
    public function test_hero_summary_roundtrip_via_to_array(): void
    {
        $original = new HeroSummary(
            tasksOverdue: 3,
            votesOpen: 1,
            signaturesPending: 0,
            nextMeetingAt: CarbonImmutable::parse('2026-05-10T14:30:00Z'),
            nextMeetingId: 42,
        );

        $restored = HeroSummary::fromArray($original->toArray());

        $this->assertEquals($original, $restored);
    }

    #[Test]
    public function test_hero_summary_from_array_vazio_usa_zeros_e_nulls(): void
    {
        $h = HeroSummary::fromArray([]);

        $this->assertSame(0, $h->tasksOverdue);
        $this->assertSame(0, $h->votesOpen);
        $this->assertSame(0, $h->signaturesPending);
        $this->assertNull($h->nextMeetingAt);
        $this->assertNull($h->nextMeetingId);
    }

    #[Test]
    public function test_operations_block_roundtrip_via_to_array(): void
    {
        $original = new OperationsBlock(
            minutesPendingReview: 2,
            meetingsThisMonth: 4,
            notificationsUnread: 9,
        );

        $restored = OperationsBlock::fromArray($original->toArray());

        $this->assertEquals($original, $restored);
    }
}
