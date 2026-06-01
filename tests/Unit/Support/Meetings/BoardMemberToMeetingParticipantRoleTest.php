<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Meetings;

use App\Enums\BoardMemberRole;
use App\Enums\MeetingParticipantRole;
use App\Support\Meetings\BoardMemberToMeetingParticipantRole;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class BoardMemberToMeetingParticipantRoleTest extends TestCase
{
    #[DataProvider('roleMappingProvider')]
    public function test_mapeia_papel_do_conselho_para_participante(
        BoardMemberRole $boardRole,
        MeetingParticipantRole $expected,
    ): void {
        $this->assertSame($expected, BoardMemberToMeetingParticipantRole::map($boardRole));
    }

    /**
     * @return array<string, array{BoardMemberRole, MeetingParticipantRole}>
     */
    public static function roleMappingProvider(): array
    {
        return [
            'chairperson' => [BoardMemberRole::Chairperson, MeetingParticipantRole::Chairperson],
            'secretary' => [BoardMemberRole::Secretary, MeetingParticipantRole::Secretary],
            'member' => [BoardMemberRole::Member, MeetingParticipantRole::Participant],
            'observer' => [BoardMemberRole::Observer, MeetingParticipantRole::Guest],
        ];
    }
}
