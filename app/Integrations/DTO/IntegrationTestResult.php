<?php

namespace App\Integrations\DTO;

final class IntegrationTestResult
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

