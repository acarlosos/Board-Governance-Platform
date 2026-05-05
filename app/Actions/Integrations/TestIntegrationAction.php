<?php

namespace App\Actions\Integrations;

use App\Enums\IntegrationLogAction;
use App\Enums\IntegrationStatus;
use App\Enums\IntegrationTestStatus;
use App\Integrations\IntegrationDriverFactory;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class TestIntegrationAction
{
    public function test(User $actor, Integration $integration): Integration
    {
        $this->assertTenantAccess($actor, $integration);

        $driver = app(IntegrationDriverFactory::class)->resolve($integration->provider);
        $result = $driver->test($integration);

        $integration->last_tested_at = \Illuminate\Support\Carbon::now();
        $integration->last_test_status = $result->success ? IntegrationTestStatus::Success : IntegrationTestStatus::Failed;
        $integration->last_test_message = $result->message;

        if (! $result->success && $integration->status === IntegrationStatus::Active) {
            $integration->status = IntegrationStatus::Error;
        }

        $integration->save();

        app(RecordIntegrationLogAction::class)->record(
            actor: $actor,
            integration: $integration,
            action: IntegrationLogAction::Tested,
            status: $result->success ? 'success' : 'failed',
            message: $result->message,
            context: $result->context,
        );

        return $integration->fresh();
    }

    private function assertTenantAccess(User $actor, Integration $integration): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->tenant_id === null || (int) $actor->tenant_id !== (int) $integration->tenant_id) {
            throw ValidationException::withMessages([
                'tenant_id' => __('integrations.validation.tenant_mismatch'),
            ]);
        }
    }
}

