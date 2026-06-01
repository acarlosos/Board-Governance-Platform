<?php

namespace App\Support\Filament;

use BackedEnum;

/**
 * Colunas Filament com model cast para enum — evitar (string) $state em PHP 8.4+.
 */
final class FormatBackedEnumState
{
    public static function value(mixed $state): string
    {
        if ($state instanceof BackedEnum) {
            return (string) $state->value;
        }

        return (string) ($state ?? '');
    }
}
