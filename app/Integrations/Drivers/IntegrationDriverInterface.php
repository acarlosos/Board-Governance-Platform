<?php

namespace App\Integrations\Drivers;

use App\Integrations\DTO\IntegrationTestResult;
use App\Models\Integration;

interface IntegrationDriverInterface
{
    public function test(Integration $integration): IntegrationTestResult;
}

