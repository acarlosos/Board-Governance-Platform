<?php

namespace App\Enums;

enum SignatureProvider: string
{
    case Internal = 'internal';
    case DocuSign = 'docusign';
}

