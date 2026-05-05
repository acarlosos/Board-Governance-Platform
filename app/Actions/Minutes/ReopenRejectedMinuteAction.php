<?php

namespace App\Actions\Minutes;

use App\Enums\MinuteStatus;
use App\Models\Minute;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class ReopenRejectedMinuteAction
{
    public function reopen(User $actor, Minute $minute): Minute
    {
        $this->assertTenantAccess($actor, $minute);

        if ($minute->status !== MinuteStatus::Rejected) {
            throw ValidationException::withMessages([
                'status' => __('minutes.validation.reopen_only_in_rejected'),
            ]);
        }

        if (! $minute->status->canTransitionTo(MinuteStatus::Draft)) {
            throw ValidationException::withMessages([
                'status' => __('minutes.validation.invalid_status_transition'),
            ]);
        }

        // Regras: rejected volta para draft; nova versão será criada separadamente.
        $minute->status = MinuteStatus::Draft;
        $minute->save();

        return $minute->fresh();
    }

    private function assertTenantAccess(User $actor, Minute $minute): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->tenant_id === null || (int) $actor->tenant_id !== (int) $minute->tenant_id) {
            throw ValidationException::withMessages([
                'tenant_id' => __('minutes.validation.tenant_mismatch'),
            ]);
        }
    }
}

