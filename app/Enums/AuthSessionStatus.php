<?php

namespace App\Enums;

enum AuthSessionStatus: string
{
    case Active = 'active';
    case Closed = 'closed';
    case Expired = 'expired';
}
