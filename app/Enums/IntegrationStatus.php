<?php

namespace App\Enums;

enum IntegrationStatus: string
{
    case Inactive = 'inactive';
    case Active = 'active';
    case Error = 'error';
}

