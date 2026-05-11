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
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            minutesPendingReview: (int) ($data['minutes_pending_review'] ?? 0),
            meetingsThisMonth: (int) ($data['meetings_this_month'] ?? 0),
            notificationsUnread: (int) ($data['notifications_unread'] ?? 0),
        );
    }

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
