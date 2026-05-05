<?php

namespace App\Services\Notifications\Drivers;

use App\Models\NotificationCenter;
use App\Models\NotificationTemplate;
use App\Services\Notifications\DTO\NotificationSendResult;

final class FakeEmailNotificationDriver implements NotificationChannelDriverInterface
{
    public function send(NotificationCenter $notification, ?NotificationTemplate $template = null): NotificationSendResult
    {
        // Nenhum envio real nesta fase.
        return new NotificationSendResult(
            success: true,
            message: __('notifications.driver.email_fake_ok'),
            context: [],
        );
    }
}

