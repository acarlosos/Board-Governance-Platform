<?php

namespace App\Enums;

enum SignatureRequestStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    /**
     * @return list<SignatureRequestStatus>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::Sent],
            self::Sent => [self::Completed, self::Cancelled, self::Failed],
            self::Completed => [],
            self::Cancelled => [],
            self::Failed => [],
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

