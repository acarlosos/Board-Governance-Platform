<?php

namespace App\Services\Dashboard\Executive\Snapshot;

final readonly class OperationsBlock
{
    public function __construct(
        public int $minutesPendingReview,
        public int $meetingsThisMonth,
        public int $notificationsUnread,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'minutes_pending_review' => $this->minutesPendingReview,
            'meetings_this_month' => $this->meetingsThisMonth,
            'notifications_unread' => $this->notificationsUnread,
        ];
    }
}
