<?php

namespace App\Policies;

use App\Models\User;

/**
 * Política mínima para o painel Filament (exemplo).
 * Apertar regras quando existir multi-tenancy e perfis.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, User $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, User $model): bool
    {
        return true;
    }

    public function delete(User $user, User $model): bool
    {
        return true;
    }
}
