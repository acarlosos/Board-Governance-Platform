<?php

namespace App\Enums;

enum IntegrationLogAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Tested = 'tested';
    case Enabled = 'enabled';
    case Disabled = 'disabled';
}

