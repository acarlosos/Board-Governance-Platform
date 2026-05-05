<?php

namespace App\Policies;

use App\Models\SignatureRequestSigner;
use App\Models\User;

class SignatureRequestSignerPolicy
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

    public function view(User $user, SignatureRequestSigner $signer): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $signer->tenant_id) {
            return false;
        }

        if ($user->hasRole('tenant_admin') || $user->can('manage_signatures') || $user->can('manage_settings')) {
            return true;
        }

        return (int) $signer->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, SignatureRequestSigner $signer): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $signer->tenant_id) {
            return false;
        }

        // admin gerencia signers; signer-user não edita cadastro (somente Actions de assinar/rejeitar)
        return $user->hasRole('tenant_admin') || $user->can('manage_signatures') || $user->can('manage_settings');
    }

    public function delete(User $user, SignatureRequestSigner $signer): bool
    {
        return $this->update($user, $signer);
    }
}

