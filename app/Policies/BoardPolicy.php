<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;

class BoardPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null) {
            return false;
        }

        // tenant_admin e permissões específicas podem gerir boards no tenant
        return $user->hasRole('tenant_admin') || $user->can('manage_boards') || $user->hasRole('board_member');
    }

    /**
     * API v1: endpoint GET /boards — escopo de listagem aplicado na Action (membros vs gestores).
     */
    public function viewAnyInApi(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id !== null;
    }

    public function view(User $user, Board $board): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $board->tenant_id) {
            return false;
        }

        if ($user->hasRole('tenant_admin') || $user->can('manage_boards')) {
            return true;
        }

        // board_member só vê boards onde é membro ativo
        return $board->boardMembers()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id !== null && ($user->hasRole('tenant_admin') || $user->can('manage_boards'));
    }

    public function update(User $user, Board $board): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $board->tenant_id) {
            return false;
        }

        return $user->hasRole('tenant_admin') || $user->can('manage_boards');
    }

    public function delete(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }

    public function restore(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }

    public function forceDelete(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }
}

