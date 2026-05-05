<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\TaskComment;
use App\Services\Audit\AuditLoggerService;

class TaskCommentObserver
{
    public function __construct(private readonly AuditLoggerService $audit)
    {
    }

    public function created(TaskComment $comment): void
    {
        // Não registrar comentário completo na auditoria (pode ser sensível). Guardar só metadados.
        $this->audit->log(
            action: AuditAction::Updated,
            auditable: $comment->task,
            oldValues: [],
            newValues: [
                'event' => 'comment_added',
                'task_comment_id' => $comment->id,
                'user_id' => $comment->user_id,
                'created_at' => $comment->created_at,
            ],
            tenantId: (int) $comment->tenant_id,
        );
    }
}

