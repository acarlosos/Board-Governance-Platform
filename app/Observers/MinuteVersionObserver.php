<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\MinuteVersion;
use App\Services\Audit\AuditLoggerService;

class MinuteVersionObserver
{
    public function __construct(private readonly AuditLoggerService $audit)
    {
    }

    public function created(MinuteVersion $version): void
    {
        // Não registrar content completo. Guardamos só metadados e summary.
        $this->audit->log(
            action: AuditAction::Updated,
            auditable: $version->minute,
            oldValues: [],
            newValues: [
                'event' => 'version_created',
                'minute_version_id' => $version->id,
                'version_number' => $version->version_number,
                'changes_summary' => $version->changes_summary,
                'created_by' => $version->created_by,
            ],
        );
    }
}

