<?php

namespace App\Support\Minutes;

final class MinuteContent
{
    public static function isBlank(?string $content): bool
    {
        if ($content === null) {
            return true;
        }

        $text = trim(strip_tags($content));

        return $text === '';
    }
}
