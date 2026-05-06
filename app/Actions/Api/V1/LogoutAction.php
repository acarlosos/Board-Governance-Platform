<?php

namespace App\Actions\Api\V1;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

final class LogoutAction
{
    public function __construct(
        private readonly AuditLoggerService $audit,
    ) {}

    public function execute(User $user, ?PersonalAccessToken $token): void
    {
        if ($token === null) {
            return;
        }

        DB::transaction(function () use ($user, $token): void {
            $tokenId = $token->getKey();
            $tenantId = $user->tenant_id;

            $token->delete();

            $this->audit->log(
                action: AuditAction::ApiLogout,
                auditable: null,
                newValues: [
                    'token_id' => $tokenId,
                ],
                actor: $user,
                tenantId: $tenantId,
            );

            $this->audit->log(
                action: AuditAction::TokenRevoked,
                auditable: null,
                newValues: [
                    'token_id' => $tokenId,
                    'revoked_current' => true,
                ],
                actor: $user,
                tenantId: $tenantId,
            );
        });
    }
}

