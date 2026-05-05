<?php

namespace App\Signatures\DTO;

final class SignatureProviderResult
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $external_id,
        public readonly string $message,
        public readonly array $context = [],
    ) {
    }
}

