<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Database = 'database';
    case Email = 'email';
}

