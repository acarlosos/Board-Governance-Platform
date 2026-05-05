<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\DocumentVersion;
use App\Services\Audit\AuditLoggerService;

class DocumentVersionObserver
{
    /**
     * IMPORTANTE: não incluir `file_path` aqui (sensível).
     */
    private const AUDITABLE_FIELDS = [
        'tenant_id',
        'document_id',
        'version_number',
        'original_name',
        'disk',
        'mime_type',
        'size',
        'checksum',
        'uploaded_by',
        'deleted_at',
    ];

    public function __construct(private readonly AuditLoggerService $audit)
    {
    }

    public function created(DocumentVersion $version): void
    {
        $this->audit->log(
            action: AuditAction::Updated,
            auditable: $version->document,
            oldValues: [],
            newValues: [
                'event' => 'version_uploaded',
                'document_version_id' => $version->id,
                'version_number' => $version->version_number,
                'original_name' => $version->original_name,
                'mime_type' => $version->mime_type,
                'size' => $version->size,
                'checksum' => $version->checksum,
            ],
        );
    }

    public function updated(DocumentVersion $version): void
    {
        $dirty = array_intersect_key($version->getChanges(), array_flip(self::AUDITABLE_FIELDS));
        if ($dirty === []) {
            return;
        }

        $this->audit->log(
            action: AuditAction::Updated,
            auditable: $version->document,
            oldValues: [
                'event' => 'version_updated',
                'document_version_id' => $version->id,
                'changes' => $this->extractOldValues($version, array_keys($dirty)),
            ],
            newValues: [
                'event' => 'version_updated',
                'document_version_id' => $version->id,
                'changes' => $this->extractNewValues($version, array_keys($dirty)),
            ],
        );
    }

    public function deleted(DocumentVersion $version): void
    {
        $this->audit->log(
            action: AuditAction::Updated,
            auditable: $version->document,
            oldValues: [
                'event' => 'version_deleted',
                'document_version_id' => $version->id,
                'version_number' => $version->version_number,
            ],
            newValues: [],
        );
    }

    public function restored(DocumentVersion $version): void
    {
        $this->audit->log(
            action: AuditAction::Updated,
            auditable: $version->document,
            oldValues: [],
            newValues: [
                'event' => 'version_restored',
                'document_version_id' => $version->id,
                'version_number' => $version->version_number,
            ],
        );
    }

    /**
     * @param  list<string>|null  $only
     * @return array<string, mixed>
     */
    private function extractNewValues(DocumentVersion $version, ?array $only = null): array
    {
        return $version->only($only ?? self::AUDITABLE_FIELDS);
    }

    /**
     * @param  list<string>|null  $only
     * @return array<string, mixed>
     */
    private function extractOldValues(DocumentVersion $version, ?array $only = null): array
    {
        $fields = $only ?? self::AUDITABLE_FIELDS;
        $old = [];
        foreach ($fields as $field) {
            $old[$field] = $version->getOriginal($field);
        }

        return $old;
    }
}

