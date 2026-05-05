<?php

namespace App\Enums;

enum SignatureEventAction: string
{
    case Created = 'created';
    case Sent = 'sent';
    case Signed = 'signed';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
    case WebhookReceived = 'webhook_received';
}

