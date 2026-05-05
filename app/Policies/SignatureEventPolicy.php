<?php

namespace App\Policies;

use App\Models\SignatureEvent;
use App\Models\User;

class SignatureEventPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null) {
            return false;
        }

        return $user->hasRole('tenant_admin')
            || $user->can('manage_signatures')
            || $user->can('manage_settings');
    }

    public function view(User $user, SignatureEvent $event): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $event->tenant_id) {
            return false;
        }

        if ($user->hasRole('tenant_admin') || $user->can('manage_signatures') || $user->can('manage_settings')) {
            return true;
        }

        // signer user pode ver eventos da própria solicitação
        return $event->request()->whereHas('signers', fn ($q) => $q->where('user_id', $user->id))->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, SignatureEvent $event): bool
    {
        return false;
    }

    public function delete(User $user, SignatureEvent $event): bool
    {
        return false;
    }
}

