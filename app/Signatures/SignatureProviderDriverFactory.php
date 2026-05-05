<?php

namespace App\Signatures;

use App\Enums\SignatureProvider;
use App\Signatures\Drivers\FakeDocuSignSignatureDriver;
use App\Signatures\Drivers\InternalSignatureDriver;
use App\Signatures\Drivers\SignatureProviderDriverInterface;

final class SignatureProviderDriverFactory
{
    public function resolve(SignatureProvider $provider): SignatureProviderDriverInterface
    {
        return match ($provider) {
            SignatureProvider::Internal => app(InternalSignatureDriver::class),
            SignatureProvider::DocuSign => app(FakeDocuSignSignatureDriver::class),
        };
    }
}

