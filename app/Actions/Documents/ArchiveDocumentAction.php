<?php

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class ArchiveDocumentAction
{
    public function archive(User $actor, Document $document): Document
    {
        $this->assertTenantAccess($actor, $document);

        $document->status = DocumentStatus::Archived;
        $document->save();

        return $document->fresh();
    }

    private function assertTenantAccess(User $actor, Document $document): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->tenant_id === null || (int) $actor->tenant_id !== (int) $document->tenant_id) {
            throw ValidationException::withMessages([
                'tenant_id' => __('documents.validation.tenant_mismatch'),
            ]);
        }
    }
}

