<?php

namespace App\Enums;

enum IntegrationType: string
{
    case Email = 'email';
    case Storage = 'storage';
    case Signature = 'signature';
    case VideoConference = 'video_conference';
    case Reporting = 'reporting';
    case Identity = 'identity';
}

