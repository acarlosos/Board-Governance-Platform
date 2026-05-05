<?php

namespace App\Actions\Integrations;

use App\Enums\IntegrationLogAction;
use App\Enums\IntegrationStatus;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class DisableIntegrationAction
{
    public function disable(User $actor, Integration $integration): Integration
    {
        $this->assertTenantAccess($actor, $integration);

        $integration->status = IntegrationStatus::Inactive;
        $integration->save();

        app(RecordIntegrationLogAction::class)->record(
            actor: $actor,
            integration: $integration,
            action: IntegrationLogAction::Disabled,
            status: 'success',
            message: __('integrations.logs.disabled'),
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

