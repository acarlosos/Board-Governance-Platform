<?php

namespace App\Actions\Boards;

use App\Enums\BoardStatus;
use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PersistBoardAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Board
    {
        $data = $this->applyTenantGuard($actor, $data, null);

        $validator = Validator::make(
            $data,
            [
                'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'status' => ['required', 'string', Rule::in(array_map(fn (BoardStatus $s) => $s->value, BoardStatus::cases()))],
            ],
            [],
            [
                'tenant_id' => __('boards.validation.attributes.tenant'),
                'name' => __('boards.validation.attributes.name'),
                'description' => __('boards.validation.attributes.description'),
                'status' => __('boards.validation.attributes.status'),
            ],
        );

        $validated = $validator->validate();

        $board = new Board;
        $board->fill(Arr::only($validated, ['tenant_id', 'name', 'description', 'status']));
        $board->created_by = $actor->getKey();
        $board->save();

        return $board->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Board $board, array $data): Board
    {
        $data = $this->applyTenantGuard($actor, $data, $board);

        $validator = Validator::make(
            $data,
            [
                'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'status' => ['required', 'string', Rule::in(array_map(fn (BoardStatus $s) => $s->value, BoardStatus::cases()))],
            ],
            [],
            [
                'tenant_id' => __('boards.validation.attributes.tenant'),
                'name' => __('boards.validation.attributes.name'),
                'description' => __('boards.validation.attributes.description'),
                'status' => __('boards.validation.attributes.status'),
            ],
        );

        $validated = $validator->validate();

        $board->fill(Arr::only($validated, ['tenant_id', 'name', 'description', 'status']));
        $board->save();

        return $board->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyTenantGuard(User $actor, array $data, ?Board $existing): array
    {
        if (! $actor->isSuperAdmin()) {
            $data['tenant_id'] = $actor->tenant_id;
        }

        if (! $actor->isSuperAdmin() && $actor->tenant_id === null) {
            throw ValidationException::withMessages([
                'tenant_id' => __('boards.validation.tenant_required'),
            ]);
        }

        if ($existing && ! $actor->isSuperAdmin()) {
            if ((int) $existing->tenant_id !== (int) $actor->tenant_id) {
                throw ValidationException::withMessages([
                    'tenant_id' => __('boards.validation.tenant_mismatch'),
                ]);
            }
        }

        return $data;
    }
}

