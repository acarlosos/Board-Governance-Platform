<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
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
            || $user->can('manage_documents')
            || $user->hasRole('board_member');
    }

    public function view(User $user, Document $document): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $document->tenant_id) {
            return false;
        }

        if ($user->hasRole('tenant_admin') || $user->can('manage_documents')) {
            return true;
        }

        // board_member: documentos de boards onde é membro ativo (direto ou via meeting->board)
        $board = $document->meeting?->board ?? $document->board;
        if ($board) {
            $isBoardMember = $board->boardMembers()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();

            if ($isBoardMember) {
                return true;
            }
        }

        // participante: documentos de reuniões onde está vinculado
        if ($document->meeting) {
            return $document->meeting->participants()
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null) {
            return false;
        }

        if ($user->hasRole('board_member')) {
            return false;
        }

        return $user->hasRole('tenant_admin') || $user->can('manage_documents');
    }

    public function update(User $user, Document $document): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id === null || (int) $user->tenant_id !== (int) $document->tenant_id) {
            return false;
        }

        if ($user->hasRole('board_member')) {
            return false;
        }

        return $user->hasRole('tenant_admin') || $user->can('manage_documents');
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }

    public function restore(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }
}

