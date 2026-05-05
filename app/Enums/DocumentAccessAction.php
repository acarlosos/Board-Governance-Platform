<?php

namespace App\Enums;

enum DocumentAccessAction: string
{
    case Viewed = 'viewed';
    case Downloaded = 'downloaded';
}

