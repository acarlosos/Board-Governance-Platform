<?php

namespace App\Enums;

enum BoardStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}

