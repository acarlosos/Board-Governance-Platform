<?php

namespace App\Support\Meetings;

use App\Enums\BoardMemberRole;
use App\Enums\MeetingParticipantRole;

final class BoardMemberToMeetingParticipantRole
{
    public static function map(BoardMemberRole $boardRole): MeetingParticipantRole
    {
        return match ($boardRole) {
            BoardMemberRole::Chairperson => MeetingParticipantRole::Chairperson,
            BoardMemberRole::Secretary => MeetingParticipantRole::Secretary,
            BoardMemberRole::Member => MeetingParticipantRole::Participant,
            BoardMemberRole::Observer => MeetingParticipantRole::Guest,
        };
    }
}
