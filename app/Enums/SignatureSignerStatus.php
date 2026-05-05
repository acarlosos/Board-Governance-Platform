<?php

namespace App\Enums;

enum SignatureSignerStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Signed = 'signed';
    case Rejected = 'rejected';

    /**
     * @return list<SignatureSignerStatus>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Pending => [self::Sent],
            self::Sent => [self::Signed, self::Rejected],
            self::Signed => [],
            self::Rejected => [],
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

