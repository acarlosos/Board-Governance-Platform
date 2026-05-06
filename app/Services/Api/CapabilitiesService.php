<?php

namespace App\Services\Api;

use App\Models\User;

final class CapabilitiesService
{
    /**
     * Keep this small and stable: UX flags only.
     *
     * @return array{can_manage_security: bool, can_manage_documents: bool, can_view_reports: bool}
     */
    public function forUser(User $user): array
    {
        return [
            'can_manage_security' => $user->can('manage_security'),
            'can_manage_documents' => $user->can('manage_documents'),
            'can_view_reports' => $user->can('view_reports'),
        ];
    }
}

