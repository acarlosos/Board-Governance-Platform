<?php

namespace App\Actions\Boards;

use App\Enums\BoardStatus;
use App\Models\Board;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class ArchiveBoardAction
{
    public function archive(User $actor, Board $board): Board
    {
        $this->assertTenantAccess($actor, $board);

        $board->status = BoardStatus::Archived;
        $board->save();

        return $board->fresh();
    }

    private function assertTenantAccess(User $actor, Board $board): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->tenant_id === null || (int) $actor->tenant_id !== (int) $board->tenant_id) {
            throw ValidationException::withMessages([
                'tenant_id' => __('boards.validation.tenant_mismatch'),
            ]);
        }
    }
}

