<?php

namespace App\Enums;

enum IntegrationProvider: string
{
    case Smtp = 'smtp';
    case Microsoft365 = 'microsoft_365';
    case OneDrive = 'onedrive';
    case DocuSign = 'docusign';
    case Teams = 'teams';
    case Zoom = 'zoom';
    case LookerStudio = 'looker_studio';
}

