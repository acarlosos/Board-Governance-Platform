<?php

namespace App\Integrations\Drivers\Concerns;

use App\Enums\IntegrationProvider;
use App\Integrations\DTO\IntegrationTestResult;
use App\Integrations\IntegrationConfigSchemaRegistry;
use App\Models\Integration;

trait ValidatesRequiredConfig
{
    protected function validateRequired(Integration $integration, IntegrationProvider $provider): IntegrationTestResult
    {
        $config = $integration->config ?? [];
        $missing = [];

        foreach (IntegrationConfigSchemaRegistry::requiredKeys($provider) as $key) {
            $value = $config[$key] ?? null;
            if ($value === null || $value === '') {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            return new IntegrationTestResult(
                success: false,
                message: __('integrations.test.missing_required'),
                context: ['missing' => $missing],
            );
        }

        return new IntegrationTestResult(
            success: true,
            message: __('integrations.test.ok'),
            context: [],
        );
    }
}

