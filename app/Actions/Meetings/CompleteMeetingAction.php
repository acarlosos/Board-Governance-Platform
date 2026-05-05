<?php

namespace App\Actions\Meetings;

use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class CompleteMeetingAction
{
    public function complete(User $actor, Meeting $meeting): Meeting
    {
        $this->assertTenantAccess($actor, $meeting);

        if ($meeting->status !== MeetingStatus::InProgress) {
            throw ValidationException::withMessages([
                'status' => __('meetings.validation.invalid_status_transition'),
            ]);
        }

        $meeting->status = MeetingStatus::Completed;
        $meeting->ends_at ??= now();
        $meeting->save();

        return $meeting->fresh();
    }

    private function assertTenantAccess(User $actor, Meeting $meeting): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->tenant_id === null || (int) $actor->tenant_id !== (int) $meeting->tenant_id) {
            throw ValidationException::withMessages([
                'tenant_id' => __('meetings.validation.tenant_mismatch'),
            ]);
        }
    }
}

