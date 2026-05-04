<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;

/**
 * Resolve o tenant actual com base no utilizador autenticado (sem subdomínio nesta fase).
 */
class TenantResolver
{
    public function currentId(): ?int
    {
        if (! auth()->check()) {
            return null;
        }

        return auth()->user()->tenant_id;
    }

    public function current(): ?Tenant
    {
        $id = $this->currentId();

        return $id ? Tenant::query()->find($id) : null;
    }
}
