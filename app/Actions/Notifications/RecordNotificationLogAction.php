<?php

namespace App\Actions\Notifications;

use App\Enums\NotificationLogStatus;
use App\Models\NotificationCenter;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Support\Arr;

final class RecordNotificationLogAction
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function record(
        ?User $actor,
        int $tenantId,
        ?NotificationCenter $notification,
        ?NotificationTemplate $template,
        string $channel,
        NotificationLogStatus|string $status,
        ?string $message = null,
        array $context = [],
    ): NotificationLog {
        return NotificationLog::query()->create([
            'tenant_id' => $tenantId,
            'notification_id' => $notification?->id,
            'template_id' => $template?->id,
            'channel' => $channel,
            'status' => $status instanceof NotificationLogStatus ? $status->value : (string) $status,
            'message' => $this->sanitizeMessage($message),
            'context' => $this->sanitizeContext($context),
            'user_id' => $actor?->id,
            'created_at' => now(),
        ]);
    }

    private function sanitizeMessage(?string $message): ?string
    {
        if ($message === null) {
            return null;
        }

        $message = trim($message);
        if ($message === '') {
            return null;
        }

        return mb_substr($message, 0, 180);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = [
            'password',
            'client_secret',
            'private_key',
            'token',
            'access_token',
            'refresh_token',
            'secret',
            'api_key',
            'metadata',
            'body',
            'subject',
            'payload',
        ];

        foreach ($sensitiveKeys as $key) {
            Arr::forget($context, $key);
        }

        return $context;
    }
}

