<?php

namespace App\Services\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationTemplateStatus;
use App\Models\NotificationTemplate;

final class NotificationTemplateResolver
{
    public function resolve(
        ?int $tenantId,
        string $key,
        string $locale,
        NotificationChannel $channel,
    ): ?NotificationTemplate {
        // 1) tenant override
        if ($tenantId) {
            $tenantTemplate = NotificationTemplate::query()
                ->where('tenant_id', $tenantId)
                ->where('key', $key)
                ->where('locale', $locale)
                ->where('channel', $channel->value)
                ->where('status', NotificationTemplateStatus::Active->value)
                ->latest('id')
                ->first();

            if ($tenantTemplate) {
                return $tenantTemplate;
            }
        }

        // 2) global fallback
        return NotificationTemplate::query()
            ->whereNull('tenant_id')
            ->where('key', $key)
            ->where('locale', $locale)
            ->where('channel', $channel->value)
            ->where('status', NotificationTemplateStatus::Active->value)
            ->latest('id')
            ->first();
    }
}

