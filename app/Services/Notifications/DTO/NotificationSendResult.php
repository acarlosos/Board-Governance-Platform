<?php

namespace App\Services\Notifications\DTO;

final class NotificationSendResult
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly array $context = [],
    ) {
    }
}

