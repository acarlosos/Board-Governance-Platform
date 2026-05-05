<?php

namespace App\Actions\Signatures;

use App\Enums\SignatureEventAction;
use App\Enums\SignatureProvider;
use App\Enums\SignatureRequestStatus;
use App\Enums\SignatureSignerStatus;
use App\Models\SignatureRequest;
use App\Models\User;
use App\Signatures\SignatureProviderDriverFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SendSignatureRequestAction
{
    public function send(User $actor, SignatureRequest $request): SignatureRequest
    {
        $this->assertTenantAccess($actor, $request);

        if (! $request->status->canTransitionTo(SignatureRequestStatus::Sent)) {
            throw ValidationException::withMessages([
                'status' => __('signatures.validation.invalid_status_transition'),
            ]);
        }

        $signersCount = $request->signers()->count();
        if ($signersCount < 1) {
            throw ValidationException::withMessages([
                'signers' => __('signatures.validation.at_least_one_signer'),
            ]);
        }

        return DB::transaction(function () use ($actor, $request): SignatureRequest {
            $driver = app(SignatureProviderDriverFactory::class)->resolve($request->provider);
            $result = $driver->send($request);

            if (! $result->success) {
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

                return $request->fresh();
            }

            $request->status = SignatureRequestStatus::Sent;
            $request->requested_at = \Illuminate\Support\Carbon::now();
            $request->external_id = $result->external_id;
            $request->save();

            // internal: marcar signers como sent (simulação)
            $request->signers()
                ->where('status', SignatureSignerStatus::Pending->value)
                ->update(['status' => SignatureSignerStatus::Sent->value]);

            app(RecordSignatureEventAction::class)->record(
                actor: $actor,
                request: $request,
                action: SignatureEventAction::Sent,
                status: $request->status->value,
                message: __('signatures.events.sent'),
                context: [],
            );

            return $request->fresh();
        });
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

