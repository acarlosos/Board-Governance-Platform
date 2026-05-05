<?php

namespace App\Policies;

use App\Models\DocumentAccessLog;
use App\Models\User;

class DocumentAccessLogPolicy
{
    public function viewAny(User $user): bool
    {
        // leitura só por quem já pode ver documentos
        return $user->isSuperAdmin()
            || $user->hasRole('tenant_admin')
            || $user->can('manage_documents')
            || $user->hasRole('board_member');
    }

    public function view(User $user, DocumentAccessLog $log): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $log->tenant_id) {
            return false;
        }

        // sem granularidade por documento aqui (rel manager ficará no documento e query já estará filtrada)
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, DocumentAccessLog $log): bool
    {
        return false;
    }

    public function delete(User $user, DocumentAccessLog $log): bool
    {
        return false;
    }
}

