<?php

namespace App\Actions\Documents;

use App\Enums\DocumentAccessAction;
use App\Models\Document;
use App\Models\DocumentAccessLog;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Http\Request;

final class RecordDocumentAccessAction
{
    public function record(?User $actor, Document $document, DocumentAccessAction $action, ?DocumentVersion $version = null, ?Request $request = null): DocumentAccessLog
    {
        $request ??= request();

        return DocumentAccessLog::query()->create([
            'tenant_id' => $document->tenant_id,
            'document_id' => $document->id,
            'document_version_id' => $version?->id,
            'user_id' => $actor?->id,
            'action' => $action->value,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}

