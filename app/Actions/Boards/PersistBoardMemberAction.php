<?php

namespace App\Actions\Boards;

use App\Enums\BoardMemberRole;
use App\Enums\BoardMemberStatus;
use App\Models\Board;
use App\Models\BoardMember;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PersistBoardMemberAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, Board $board, array $data): BoardMember
    {
        $this->assertBoardTenantAccess($actor, $board);

        $data['tenant_id'] = $board->tenant_id;
        $data['board_id'] = $board->getKey();

        $validator = Validator::make(
            $data,
            [
                'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
                'board_id' => ['required', 'integer', 'exists:boards,id'],
                'user_id' => ['required', 'integer', 'exists:users,id'],
                'role' => ['required', 'string', Rule::in(array_map(fn (BoardMemberRole $r) => $r->value, BoardMemberRole::cases()))],
                'status' => ['required', 'string', Rule::in(array_map(fn (BoardMemberStatus $s) => $s->value, BoardMemberStatus::cases()))],
                'joined_at' => ['nullable', 'date'],
                'left_at' => ['nullable', 'date'],
            ],
            [],
            [
                'user_id' => __('board-members.validation.attributes.user'),
                'role' => __('board-members.validation.attributes.role'),
                'status' => __('board-members.validation.attributes.status'),
                'joined_at' => __('board-members.validation.attributes.joined_at'),
                'left_at' => __('board-members.validation.attributes.left_at'),
            ],
        );

        $validator->after(function (\Illuminate\Validation\Validator $v) use ($board): void {
            $userId = $v->safe()->user_id ?? null;
            if (! $userId) {
                return;
            }

            $user = \App\Models\User::query()->find($userId);
            if (! $user) {
                return;
            }

            if ((int) $user->tenant_id !== (int) $board->tenant_id) {
                $v->errors()->add('user_id', __('board-members.validation.user_must_belong_to_board_tenant'));
            }

            $existsActive = BoardMember::query()
                ->where('tenant_id', $board->tenant_id)
                ->where('board_id', $board->id)
                ->where('user_id', $userId)
                ->where('status', BoardMemberStatus::Active->value)
                ->exists();

            if ($existsActive) {
                $v->errors()->add('user_id', __('board-members.validation.duplicate_active_member'));
            }
        });

        $validated = $validator->validate();

        // Se existia soft-deleted, reativar em vez de inserir novo (mantém unique).
        $existing = BoardMember::withTrashed()
            ->where('tenant_id', $board->tenant_id)
            ->where('board_id', $board->id)
            ->where('user_id', $validated['user_id'])
            ->first();

        if ($existing) {
            $existing->restore();
            $existing->fill(Arr::only($validated, ['role', 'status', 'joined_at', 'left_at']));
            $existing->save();

            return $existing->fresh();
        }

        $member = new BoardMember;
        $member->fill(Arr::only($validated, ['tenant_id', 'board_id', 'user_id', 'role', 'status', 'joined_at', 'left_at']));
        $member->save();

        return $member->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, BoardMember $member, array $data): BoardMember
    {
        $this->assertBoardTenantAccess($actor, $member->board);

        $data['tenant_id'] = $member->tenant_id;
        $data['board_id'] = $member->board_id;
        $data['user_id'] = $member->user_id;

        $validator = Validator::make(
            $data,
            [
                'role' => ['required', 'string', Rule::in(array_map(fn (BoardMemberRole $r) => $r->value, BoardMemberRole::cases()))],
                'status' => ['required', 'string', Rule::in(array_map(fn (BoardMemberStatus $s) => $s->value, BoardMemberStatus::cases()))],
                'joined_at' => ['nullable', 'date'],
                'left_at' => ['nullable', 'date'],
            ],
            [],
            [
                'role' => __('board-members.validation.attributes.role'),
                'status' => __('board-members.validation.attributes.status'),
                'joined_at' => __('board-members.validation.attributes.joined_at'),
                'left_at' => __('board-members.validation.attributes.left_at'),
            ],
        );

        $validator->after(function (\Illuminate\Validation\Validator $v) use ($member): void {
            $status = (string) ($v->safe()->status ?? '');
            if ($status !== BoardMemberStatus::Active->value) {
                return;
            }

            $existsActive = BoardMember::query()
                ->where('tenant_id', $member->tenant_id)
                ->where('board_id', $member->board_id)
                ->where('user_id', $member->user_id)
                ->where('status', BoardMemberStatus::Active->value)
                ->whereKeyNot($member->getKey())
                ->exists();

            if ($existsActive) {
                $v->errors()->add('status', __('board-members.validation.duplicate_active_member'));
            }
        });

        $validated = $validator->validate();

        $member->fill(Arr::only($validated, ['role', 'status', 'joined_at', 'left_at']));
        $member->save();

        return $member->fresh();
    }

    public function remove(User $actor, BoardMember $member): void
    {
        $this->assertBoardTenantAccess($actor, $member->board);

        $member->delete();
    }

    private function assertBoardTenantAccess(User $actor, Board $board): void
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

