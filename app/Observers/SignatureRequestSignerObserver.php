<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\SignatureRequestSigner;
use App\Services\Audit\AuditLoggerService;

class SignatureRequestSignerObserver
{
    private const AUDITABLE_FIELDS = [
        'tenant_id',
        'signature_request_id',
        'user_id',
        'name',
        'email',
        'status',
        'signing_order',
        'signed_at',
        'rejected_at',
        'external_id',
        'deleted_at',
    ];

    public function __construct(private readonly AuditLoggerService $audit)
    {
    }

    public function created(SignatureRequestSigner $signer): void
    {
        $this->audit->log(
            action: AuditAction::Created,
            auditable: $signer,
            oldValues: [],
            newValues: $signer->only(self::AUDITABLE_FIELDS),
            tenantId: (int) $signer->tenant_id,
        );
    }

    public function updated(SignatureRequestSigner $signer): void
    {
        $dirty = array_intersect_key($signer->getChanges(), array_flip(self::AUDITABLE_FIELDS));
        if ($dirty === []) {
            return;
        }

        $action = array_key_exists('status', $dirty) ? AuditAction::StatusChanged : AuditAction::Updated;

        $old = [];
        foreach (array_keys($dirty) as $field) {
            $old[$field] = $signer->getOriginal($field);
        }

        $this->audit->log(
            action: $action,
            auditable: $signer,
            oldValues: $old,
            newValues: $signer->only(array_keys($dirty)),
            tenantId: (int) $signer->tenant_id,
        );
    }

    public function deleted(SignatureRequestSigner $signer): void
    {
        $this->audit->log(
            action: AuditAction::Deleted,
            auditable: $signer,
            oldValues: $signer->only(self::AUDITABLE_FIELDS),
            newValues: [],
            tenantId: (int) $signer->tenant_id,
        );
    }

    public function restored(SignatureRequestSigner $signer): void
    {
        $this->audit->log(
            action: AuditAction::Restored,
            auditable: $signer,
            oldValues: [],
            newValues: $signer->only(self::AUDITABLE_FIELDS),
            tenantId: (int) $signer->tenant_id,
        );
    }
}

