<?php

namespace App\Actions\Integrations;

use App\Enums\IntegrationLogAction;
use App\Enums\IntegrationStatus;
use App\Enums\IntegrationTestStatus;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class EnableIntegrationAction
{
    public function enable(User $actor, Integration $integration): Integration
    {
        $this->assertTenantAccess($actor, $integration);

        if ($integration->last_test_status !== IntegrationTestStatus::Success) {
            throw ValidationException::withMessages([
                'status' => __('integrations.validation.enable_requires_successful_test'),
            ]);
        }

        $integration->status = IntegrationStatus::Active;
        $integration->save();

        app(RecordIntegrationLogAction::class)->record(
            actor: $actor,
            integration: $integration,
            action: IntegrationLogAction::Enabled,
            status: 'success',
            message: __('integrations.logs.enabled'),
            context: [],
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

