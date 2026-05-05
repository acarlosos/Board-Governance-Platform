<?php

namespace App\Enums;

enum BoardMemberRole: string
{
    case Chairperson = 'chairperson';
    case Member = 'member';
    case Secretary = 'secretary';
    case Observer = 'observer';
}

