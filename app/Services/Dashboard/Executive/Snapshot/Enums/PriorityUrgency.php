<?php

namespace App\Services\Dashboard\Executive\Snapshot\Enums;

enum PriorityUrgency: string
{
    case Overdue = 'overdue';
    case DueToday = 'due_today';
    case DueThisWeek = 'due_this_week';
    case Normal = 'normal';
}
