<?php

namespace App\Actions\Minutes;

use App\Enums\MinuteStatus;
use App\Models\Minute;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class ArchiveMinuteAction
{
    public function archive(User $actor, Minute $minute): Minute
    {
        $this->assertTenantAccess($actor, $minute);

        if ($minute->status !== MinuteStatus::Approved) {
            throw ValidationException::withMessages([
                'status' => __('minutes.validation.archive_only_in_approved'),
            ]);
        }

        if (! $minute->status->canTransitionTo(MinuteStatus::Archived)) {
            throw ValidationException::withMessages([
                'status' => __('minutes.validation.invalid_status_transition'),
            ]);
        }

        $minute->status = MinuteStatus::Archived;
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

