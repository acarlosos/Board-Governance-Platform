<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\Document;
use App\Services\Audit\AuditLoggerService;

class DocumentObserver
{
    private const AUDITABLE_FIELDS = [
        'tenant_id',
        'board_id',
        'meeting_id',
        'title',
        'description',
        'category',
        'status',
        'current_version_id',
        'uploaded_by',
        'deleted_at',
    ];

    public function __construct(private readonly AuditLoggerService $audit)
    {
    }

    public function created(Document $document): void
    {
        $this->audit->log(
            action: AuditAction::Created,
            auditable: $document,
            oldValues: [],
            newValues: $this->extractNewValues($document),
        );
    }

    public function updated(Document $document): void
    {
        $dirty = array_intersect_key($document->getChanges(), array_flip(self::AUDITABLE_FIELDS));
        if ($dirty === []) {
            return;
        }

        $action = array_key_exists('status', $dirty) ? AuditAction::StatusChanged : AuditAction::Updated;

        $this->audit->log(
            action: $action,
            auditable: $document,
            oldValues: $this->extractOldValues($document, array_keys($dirty)),
            newValues: $this->extractNewValues($document, array_keys($dirty)),
        );
    }

    public function deleted(Document $document): void
    {
        $this->audit->log(
            action: AuditAction::Deleted,
            auditable: $document,
            oldValues: $this->extractOldValues($document),
            newValues: [],
        );
    }

    public function restored(Document $document): void
    {
        $this->audit->log(
            action: AuditAction::Restored,
            auditable: $document,
            oldValues: [],
            newValues: $this->extractNewValues($document),
        );
    }

    /**
     * @param  list<string>|null  $only
     * @return array<string, mixed>
     */
    private function extractNewValues(Document $document, ?array $only = null): array
    {
        $values = $document->only($only ?? self::AUDITABLE_FIELDS);

        return $values;
    }

    /**
     * @param  list<string>|null  $only
     * @return array<string, mixed>
     */
    private function extractOldValues(Document $document, ?array $only = null): array
    {
        $fields = $only ?? self::AUDITABLE_FIELDS;
        $old = [];
        foreach ($fields as $field) {
            $old[$field] = $document->getOriginal($field);
        }

        return $old;
    }
}

