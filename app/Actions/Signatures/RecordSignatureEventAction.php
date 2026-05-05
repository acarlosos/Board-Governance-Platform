<?php

namespace App\Actions\Signatures;

use App\Enums\SignatureEventAction;
use App\Models\SignatureEvent;
use App\Models\SignatureRequest;
use App\Models\SignatureRequestSigner;
use App\Models\User;
use Illuminate\Support\Arr;

final class RecordSignatureEventAction
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function record(
        ?User $actor,
        SignatureRequest $request,
        SignatureEventAction|string $action,
        ?SignatureRequestSigner $signer = null,
        ?string $status = null,
        ?string $message = null,
        array $context = [],
    ): SignatureEvent {
        return SignatureEvent::query()->create([
            'tenant_id' => $request->tenant_id,
            'signature_request_id' => $request->id,
            'signer_id' => $signer?->id,
            'action' => $action instanceof SignatureEventAction ? $action->value : (string) $action,
            'status' => $status,
            'message' => $this->sanitizeMessage($message),
            'context' => $this->sanitizeContext($context),
            'user_id' => $actor?->id,
            'created_at' => now(),
        ]);
    }

    private function sanitizeMessage(?string $message): ?string
    {
        if ($message === null) {
            return null;
        }

        // Nesta fase, evitar persistir mensagens potencialmente sensíveis. Manter curta e genérica.
        $message = trim($message);
        if ($message === '') {
            return null;
        }

        return mb_substr($message, 0, 180);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = [
            'password',
            'client_secret',
            'private_key',
            'token',
            'access_token',
            'refresh_token',
            'secret',
            'api_key',
            'metadata',
            'message',
            'payload',
        ];

        foreach ($sensitiveKeys as $key) {
            Arr::forget($context, $key);
        }

        return $context;
    }
}

