<?php

namespace App\Services\Notifications;

use App\Enums\NotificationChannel;
use App\Services\Notifications\Drivers\DatabaseNotificationDriver;
use App\Services\Notifications\Drivers\FakeEmailNotificationDriver;
use App\Services\Notifications\Drivers\NotificationChannelDriverInterface;

final class NotificationChannelDriverFactory
{
    public function resolve(NotificationChannel $channel): NotificationChannelDriverInterface
    {
        return match ($channel) {
            NotificationChannel::Database => app(DatabaseNotificationDriver::class),
            NotificationChannel::Email => app(FakeEmailNotificationDriver::class),
        };
    }
}

