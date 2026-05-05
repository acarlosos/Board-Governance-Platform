<?php

namespace App\Enums;

enum VoteStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    /**
     * @return list<VoteStatus>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::Open, self::Cancelled],
            self::Open => [self::Closed, self::Cancelled],
            self::Closed => [],
            self::Cancelled => [],
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

