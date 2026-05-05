<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\NotificationTemplate;
use App\Services\Audit\AuditLoggerService;

class NotificationTemplateObserver
{
    private const AUDITABLE_FIELDS = [
        'tenant_id',
        'key',
        'name',
        'locale',
        'channel',
        'status',
        'created_by',
        'deleted_at',
    ];

    public function __construct(private readonly AuditLoggerService $audit)
    {
    }

    public function created(NotificationTemplate $template): void
    {
        $this->audit->log(
            action: AuditAction::Created,
            auditable: $template,
            oldValues: [],
            newValues: $template->only(self::AUDITABLE_FIELDS),
            tenantId: $template->tenant_id,
        );
    }

    public function updated(NotificationTemplate $template): void
    {
        $dirty = array_intersect_key($template->getChanges(), array_flip(self::AUDITABLE_FIELDS));
        $contentChanged = array_key_exists('subject', $template->getChanges()) || array_key_exists('body', $template->getChanges());

        if ($dirty === [] && ! $contentChanged) {
            return;
        }

        $old = [];
        foreach (array_keys($dirty) as $field) {
            $old[$field] = $template->getOriginal($field);
        }

        $new = $template->only(array_keys($dirty));
        if ($contentChanged) {
            // Nunca auditar body completo; apenas sinalizar mudança
            $new['content_changed'] = true;
            $old['content_changed'] = true;
        }

        $action = array_key_exists('status', $dirty) ? AuditAction::StatusChanged : AuditAction::Updated;

        $this->audit->log(
            action: $action,
            auditable: $template,
            oldValues: $old,
            newValues: $new,
            tenantId: $template->tenant_id,
        );
    }
}

