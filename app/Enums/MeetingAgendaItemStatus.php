<?php

namespace App\Enums;

enum MeetingAgendaItemStatus: string
{
    case Pending = 'pending';
    case Discussed = 'discussed';
    case Postponed = 'postponed';
    case Cancelled = 'cancelled';
}

