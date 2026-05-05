<?php

namespace App\Signatures\Drivers;

use App\Models\SignatureRequest;
use App\Signatures\DTO\SignatureProviderResult;

interface SignatureProviderDriverInterface
{
    public function send(SignatureRequest $request): SignatureProviderResult;
}

