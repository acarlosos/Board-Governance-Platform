<?php

namespace App\Signatures\Drivers;

use App\Models\SignatureRequest;
use App\Signatures\DTO\SignatureProviderResult;

final class InternalSignatureDriver implements SignatureProviderDriverInterface
{
    public function send(SignatureRequest $request): SignatureProviderResult
    {
        // Nenhum envio real nesta fase.
        return new SignatureProviderResult(
            success: true,
            external_id: null,
            message: __('signatures.driver.internal_sent'),
            context: [],
        );
    }
}

