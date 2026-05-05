<?php

namespace App\Actions\Signatures;

use App\Enums\SignatureEventAction;
use App\Enums\SignatureRequestStatus;
use App\Models\SignatureRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class CancelSignatureRequestAction
{
    public function cancel(User $actor, SignatureRequest $request): SignatureRequest
    {
        $this->assertTenantAccess($actor, $request);

        if (! $request->status->canTransitionTo(SignatureRequestStatus::Cancelled)) {
            throw ValidationException::withMessages([
                'status' => __('signatures.validation.invalid_status_transition'),
            ]);
        }

        $request->status = SignatureRequestStatus::Cancelled;
        $request->cancelled_at = \Illuminate\Support\Carbon::now();
        $request->save();

        app(RecordSignatureEventAction::class)->record(
            actor: $actor,
            request: $request,
            action: SignatureEventAction::Cancelled,
            status: $request->status->value,
            message: __('signatures.events.cancelled'),
            context: [],
        );

        return $request->fresh();
    }

    private function assertTenantAccess(User $actor, SignatureRequest $request): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->tenant_id === null || (int) $actor->tenant_id !== (int) $request->tenant_id) {
            throw ValidationException::withMessages([
                'tenant_id' => __('signatures.validation.tenant_mismatch'),
            ]);
        }
    }
}

