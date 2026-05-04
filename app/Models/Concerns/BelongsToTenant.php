<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/**
 * Use em models com coluna `tenant_id` (não aplicar em {@see \App\Models\Tenant} nem em {@see \App\Models\User} nesta fase).
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('tenant_id') !== null) {
                return;
            }

            if (! auth()->check()) {
                return;
            }

            $user = auth()->user();

            if ($user->shouldBypassTenantScope()) {
                return;
            }

            if ($user->tenant_id !== null) {
                $model->setAttribute('tenant_id', $user->tenant_id);
            }
        });
    }
}
