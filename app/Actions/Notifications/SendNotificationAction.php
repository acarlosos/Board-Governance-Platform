<?php

namespace App\Actions\Notifications;

use App\Enums\NotificationLogStatus;
use App\Enums\NotificationStatus;
use App\Models\NotificationCenter;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\Notifications\NotificationChannelDriverFactory;
use Illuminate\Validation\ValidationException;

final class SendNotificationAction
{
    public function send(User $actor, NotificationCenter $notification, ?NotificationTemplate $template = null): NotificationCenter
    {
        $this->assertTenantAccess($actor, $notification);

        $driver = app(NotificationChannelDriverFactory::class)->resolve($notification->channel);
        $result = $driver->send($notification, $template);

        if ($result->success) {
            $notification->status = NotificationStatus::Sent;
            $notification->sent_at = \Illuminate\Support\Carbon::now();
        } else {
            $notification->status = NotificationStatus::Failed;
        }

        $notification->save();

        app(RecordNotificationLogAction::class)->record(
            actor: $actor,
            tenantId: (int) $notification->tenant_id,
            notification: $notification,
            template: $template,
            channel: $notification->channel->value,
            status: $result->success ? NotificationLogStatus::Success : NotificationLogStatus::Failed,
            message: $result->message,
            context: $result->context,
        );

        return $notification->fresh();
    }

    private function assertTenantAccess(User $actor, NotificationCenter $notification): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->tenant_id === null || (int) $actor->tenant_id !== (int) $notification->tenant_id) {
            throw ValidationException::withMessages([
                'tenant_id' => __('notifications.validation.tenant_mismatch'),
            ]);
        }
    }
}

