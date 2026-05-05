<?php

namespace App\Enums;

enum MeetingParticipantStatus: string
{
    case Invited = 'invited';
    case Confirmed = 'confirmed';
    case Declined = 'declined';
    case Absent = 'absent';
}

