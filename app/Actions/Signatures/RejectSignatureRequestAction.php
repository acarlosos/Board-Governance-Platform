<?php

namespace App\Actions\Signatures;

use App\Enums\SignatureEventAction;
use App\Enums\SignatureProvider;
use App\Enums\SignatureRequestStatus;
use App\Enums\SignatureSignerStatus;
use App\Models\SignatureRequestSigner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RejectSignatureRequestAction
{
    public function reject(User $actor, SignatureRequestSigner $signer, ?string $reason = null): SignatureRequestSigner
    {
        $request = $signer->request;

        $this->assertSignerAccess($actor, $signer);

        if ($request->provider !== SignatureProvider::Internal) {
            throw ValidationException::withMessages([
                'provider' => __('signatures.validation.only_internal_can_sign_here'),
            ]);
        }

        if ($request->status !== SignatureRequestStatus::Sent) {
            throw ValidationException::withMessages([
                'status' => __('signatures.validation.request_must_be_sent'),
            ]);
        }

        if (! $signer->status->canTransitionTo(SignatureSignerStatus::Rejected)) {
            throw ValidationException::withMessages([
                'status' => __('signatures.validation.invalid_signer_transition'),
            ]);
        }

        return DB::transaction(function () use ($actor, $signer, $request, $reason): SignatureRequestSigner {
            $signer->status = SignatureSignerStatus::Rejected;
            $signer->rejected_at = \Illuminate\Support\Carbon::now();
            $signer->rejection_reason = $reason ? mb_substr(trim($reason), 0, 180) : null;
            $signer->save();

            app(RecordSignatureEventAction::class)->record(
                actor: $actor,
                request: $request,
                action: SignatureEventAction::Rejected,
                signer: $signer,
                status: $signer->status->value,
                message: __('signatures.events.rejected'),
                context: [],
            );

            if (! $request->status->canTransitionTo(SignatureRequestStatus::Failed)) {
                throw ValidationException::withMessages([
                    'status' => __('signatures.validation.invalid_status_transition'),
                ]);
            }

            $request->status = SignatureRequestStatus::Failed;
            $request->save();

            app(RecordSignatureEventAction::class)->record(
                actor: $actor,
                request: $request,
                action: SignatureEventAction::Failed,
                status: $request->status->value,
                message: __('signatures.events.failed'),
                context: [],
            );

            return $signer->fresh();
        });
    }

    private function assertSignerAccess(User $actor, SignatureRequestSigner $signer): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->tenant_id === null || (int) $actor->tenant_id !== (int) $signer->tenant_id) {
            throw ValidationException::withMessages([
                'tenant_id' => __('signatures.validation.tenant_mismatch'),
            ]);
        }

        if ((int) $signer->user_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'signer_id' => __('signatures.validation.not_allowed'),
            ]);
        }
    }
}

