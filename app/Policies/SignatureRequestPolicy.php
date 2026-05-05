<?php

namespace App\Policies;

use App\Models\SignatureRequest;
use App\Models\User;

class SignatureRequestPolicy
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

    public function view(User $user, SignatureRequest $request): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $request->tenant_id) {
            return false;
        }

        if ($user->hasRole('tenant_admin') || $user->can('manage_signatures') || $user->can('manage_settings')) {
            return true;
        }

        if ((int) $request->requested_by === (int) $user->id) {
            return true;
        }

        // signer user pode ver a sua solicitação
        return $request->signers()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, SignatureRequest $request): bool
    {
        if (! $this->view($user, $request)) {
            return false;
        }

        // edição de campos só em draft (state machine em Actions também)
        return $request->status->value === 'draft'
            && ($user->isSuperAdmin() || $user->hasRole('tenant_admin') || $user->can('manage_signatures') || $user->can('manage_settings'));
    }

    public function delete(User $user, SignatureRequest $request): bool
    {
        return $user->isSuperAdmin()
            || ($user->tenant_id !== null && (int) $user->tenant_id === (int) $request->tenant_id
                && ($user->hasRole('tenant_admin') || $user->can('manage_signatures') || $user->can('manage_settings')));
    }

    public function restore(User $user, SignatureRequest $request): bool
    {
        return $this->delete($user, $request);
    }

    public function forceDelete(User $user, SignatureRequest $request): bool
    {
        return $this->delete($user, $request);
    }
}

