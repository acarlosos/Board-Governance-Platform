<?php

namespace App\Actions\Api\V1\Notifications;

use App\Enums\NotificationStatus;
use App\Models\NotificationCenter;
use App\Models\User;
use Carbon\CarbonImmutable;

final class MarkAllNotificationsAsReadApiAction
{
    /**
     * @return array{affected: int, marked_at: string}
     */
    public function execute(User $actor): array
    {
        $markedAt = CarbonImmutable::now();

        $builder = NotificationCenter::query()->withoutGlobalScopes();

        if (! $actor->isSuperAdmin()) {
            if ($actor->tenant_id === null) {
                return ['affected' => 0, 'marked_at' => $markedAt->toISOString()];
            }

            $builder->where('tenant_id', $actor->tenant_id);
        }

        $builder->where('user_id', $actor->getKey());
        $builder->where('status', NotificationStatus::Unread->value);

        $affected = (int) $builder->update([
            'status' => NotificationStatus::Read->value,
            'read_at' => $markedAt,
        ]);

        return [
            'affected' => $affected,
            'marked_at' => $markedAt->toISOString(),
        ];
    }
}

