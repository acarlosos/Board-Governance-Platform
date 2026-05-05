<?php

namespace App\Actions\Notifications;

use App\Enums\NotificationStatus;
use App\Models\NotificationCenter;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class MarkNotificationAsReadAction
{
    public function mark(User $actor, NotificationCenter $notification): NotificationCenter
    {
        if (! $actor->isSuperAdmin()) {
            if ($actor->tenant_id === null || (int) $actor->tenant_id !== (int) $notification->tenant_id) {
                throw ValidationException::withMessages([
                    'tenant_id' => __('notifications.validation.tenant_mismatch'),
                ]);
            }

            if ((int) $notification->user_id !== (int) $actor->id) {
                throw ValidationException::withMessages([
                    'notification_id' => __('notifications.validation.not_allowed'),
                ]);
            }
        }

        $notification->status = NotificationStatus::Read;
        $notification->read_at = \Illuminate\Support\Carbon::now();
        $notification->save();

        return $notification->fresh();
    }
}

