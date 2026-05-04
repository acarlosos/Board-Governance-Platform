<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restringe queries ao tenant do utilizador autenticado.
 *
 * Não aplica quando não há sessão (migrations, seeders, `artisan`, testes sem `actingAs`).
 * Utilizadores com `is_super_admin` ignoram o filtro (flag bootstrap até Spatie / Fase 2).
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        if ($user->shouldBypassTenantScope()) {
            return;
        }

        $column = $model->getTable().'.tenant_id';

        if ($user->tenant_id !== null) {
            $builder->where($column, $user->tenant_id);

            return;
        }

        $builder->whereRaw('0 = 1');
    }
}
