<?php

namespace App\Enums;

enum IntegrationTestStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
}

