<?php

namespace App\Integrations\Drivers;

use App\Enums\IntegrationProvider;
use App\Integrations\DTO\IntegrationTestResult;
use App\Integrations\Drivers\Concerns\ValidatesRequiredConfig;
use App\Models\Integration;

final class TeamsIntegrationDriver implements IntegrationDriverInterface
{
    use ValidatesRequiredConfig;

    public function test(Integration $integration): IntegrationTestResult
    {
        return $this->validateRequired($integration, IntegrationProvider::Teams);
    }
}

