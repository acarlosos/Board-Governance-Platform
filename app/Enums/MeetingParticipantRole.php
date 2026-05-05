<?php

namespace App\Enums;

enum MeetingParticipantRole: string
{
    case Chairperson = 'chairperson';
    case Participant = 'participant';
    case Secretary = 'secretary';
    case Guest = 'guest';
}

