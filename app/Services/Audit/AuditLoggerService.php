<?php

namespace App\Services\Audit;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Tenancy\TenantResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

final class AuditLoggerService
{
    /**
     * Chaves que nunca devem ser persistidas na auditoria.
     *
     * @var list<string>
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'current_password',
        'new_password',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'secret',
    ];

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function log(
        AuditAction|string $action,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        ?User $actor = null,
        ?int $tenantId = null,
        ?Request $request = null,
    ): AuditLog {
        $actor ??= auth()->user();
        $tenantId ??= app(TenantResolver::class)->currentId();

        $request ??= request();

        $oldValues = $this->sanitizeValues($oldValues);
        $newValues = $this->sanitizeValues($newValues);

        return AuditLog::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $actor?->getKey(),
            'action' => $action instanceof AuditAction ? $action->value : (string) $action,
            'auditable_type' => $auditable ? $auditable->getMorphClass() : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => empty($oldValues) ? null : $oldValues,
            'new_values' => empty($newValues) ? null : $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function sanitizeValues(array $values): array
    {
        foreach (self::SENSITIVE_KEYS as $key) {
            Arr::forget($values, $key);
        }

        return $values;
    }
}

