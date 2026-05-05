<?php

namespace App\Services\Notifications\Drivers;

use App\Models\NotificationCenter;
use App\Models\NotificationTemplate;
use App\Services\Notifications\DTO\NotificationSendResult;

final class DatabaseNotificationDriver implements NotificationChannelDriverInterface
{
    public function send(NotificationCenter $notification, ?NotificationTemplate $template = null): NotificationSendResult
    {
        // "database" é interno (apenas garante que registro existe).
        return new NotificationSendResult(
            success: true,
            message: __('notifications.driver.database_ok'),
            context: [],
        );
    }
}

