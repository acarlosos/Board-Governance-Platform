<?php

namespace App\Enums;

enum MinuteStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Archived = 'archived';

    /**
     * @return list<MinuteStatus>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::InReview],
            self::InReview => [self::Approved, self::Rejected],
            self::Rejected => [self::Draft],
            self::Approved => [self::Archived],
            self::Archived => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        foreach ($this->allowedNext() as $allowed) {
            if ($allowed === $to) {
                return true;
            }
        }

        return false;
    }
}

