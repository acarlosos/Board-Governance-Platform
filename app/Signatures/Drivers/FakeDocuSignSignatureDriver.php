<?php

namespace App\Signatures\Drivers;

use App\Models\SignatureRequest;
use App\Signatures\DTO\SignatureProviderResult;
use Illuminate\Support\Str;

final class FakeDocuSignSignatureDriver implements SignatureProviderDriverInterface
{
    public function send(SignatureRequest $request): SignatureProviderResult
    {
        // Nenhuma chamada real nesta fase. Só simula um external_id.
        $externalId = 'fake_docusign_'.Str::uuid()->toString();

        return new SignatureProviderResult(
            success: true,
            external_id: $externalId,
            message: __('signatures.driver.docusign_fake_sent'),
            context: [],
        );
    }
}

