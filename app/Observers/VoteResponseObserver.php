<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\VoteResponse;
use App\Services\Audit\AuditLoggerService;

class VoteResponseObserver
{
    public function __construct(private readonly AuditLoggerService $audit)
    {
    }

    public function created(VoteResponse $response): void
    {
        // Não registrar comment (pode ser sensível). Actor já fica em audit_logs.user_id.
        $this->audit->log(
            action: AuditAction::Updated,
            auditable: $response->vote,
            oldValues: [],
            newValues: [
                'event' => 'vote_cast',
                'vote_response_id' => $response->id,
                'vote_option_id' => $response->vote_option_id,
                'voted_at' => $response->voted_at,
            ],
        );
    }
}

