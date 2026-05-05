<?php

namespace App\Actions\Integrations;

use App\Enums\IntegrationLogAction;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Models\User;
use Illuminate\Support\Arr;

final class RecordIntegrationLogAction
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function record(
        ?User $actor,
        Integration $integration,
        IntegrationLogAction|string $action,
        string $status,
        ?string $message = null,
        array $context = [],
    ): IntegrationLog {
        return IntegrationLog::query()->create([
            'tenant_id' => $integration->tenant_id,
            'integration_id' => $integration->id,
            'action' => $action instanceof IntegrationLogAction ? $action->value : (string) $action,
            'status' => $status,
            'message' => $message,
            'context' => $this->sanitizeContext($context),
            'user_id' => $actor?->id,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = [
            'password',
            'client_secret',
            'private_key',
            'token',
            'access_token',
            'refresh_token',
            'secret',
            'api_key',
        ];

        foreach ($sensitiveKeys as $key) {
            Arr::forget($context, $key);
        }

        return $context;
    }
}

