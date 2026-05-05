<?php

namespace App\Services\Notifications\Drivers;

use App\Models\NotificationCenter;
use App\Models\NotificationTemplate;
use App\Services\Notifications\DTO\NotificationSendResult;

interface NotificationChannelDriverInterface
{
    public function send(NotificationCenter $notification, ?NotificationTemplate $template = null): NotificationSendResult;
}

