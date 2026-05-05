<?php

namespace App\Policies;

use App\Enums\VoteType;
use App\Models\User;
use App\Models\VoteResponse;

class VoteResponsePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null) {
            return false;
        }

        return $user->hasRole('tenant_admin') || $user->can('manage_votes');
    }

    public function view(User $user, VoteResponse $response): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $response->tenant_id) {
            return false;
        }

        // Em votação secreta, não permitir enumerar respostas individuais (evita expor identidade).
        if ($response->vote->type === VoteType::Secret) {
            return $user->hasRole('tenant_admin') || $user->can('manage_votes');
        }

        // Em votação aberta, admin vê tudo; usuário vê o próprio voto.
        if ($user->hasRole('tenant_admin') || $user->can('manage_votes')) {
            return true;
        }

        return (int) $response->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        // votar é via Action; policy de create direta não é usada
        return false;
    }

    public function update(User $user, VoteResponse $response): bool
    {
        return false;
    }

    public function delete(User $user, VoteResponse $response): bool
    {
        return false;
    }
}

