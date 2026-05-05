<?php

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Models\Board;
use App\Models\Document;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PersistDocumentAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Document
    {
        $data = $this->applyTenantGuard($actor, $data, null);

        $validator = Validator::make(
            $data,
            [
                'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
                'board_id' => ['nullable', 'integer', 'exists:boards,id'],
                'meeting_id' => ['nullable', 'integer', 'exists:meetings,id'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'category' => ['nullable', 'string', 'max:255'],
                'status' => ['required', 'string', Rule::in(array_map(fn (DocumentStatus $s) => $s->value, DocumentStatus::cases()))],
            ],
            [],
            [
                'tenant_id' => __('documents.validation.attributes.tenant'),
                'board_id' => __('documents.validation.attributes.board'),
                'meeting_id' => __('documents.validation.attributes.meeting'),
                'title' => __('documents.validation.attributes.title'),
                'description' => __('documents.validation.attributes.description'),
                'category' => __('documents.validation.attributes.category'),
                'status' => __('documents.validation.attributes.status'),
            ],
        );

        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            $safe = $v->safe();
            $tenantId = $safe->tenant_id ?? null;
            $boardId = $safe->board_id ?? null;
            $meetingId = $safe->meeting_id ?? null;
            if (! $tenantId) {
                return;
            }

            if ($boardId) {
                $board = Board::query()->withoutGlobalScopes()->find($boardId);
                if (! $board || (int) $board->tenant_id !== (int) $tenantId) {
                    $v->errors()->add('board_id', __('documents.validation.board_must_belong_to_tenant'));
                }
            }

            if ($meetingId) {
                $meeting = Meeting::query()->withoutGlobalScopes()->find($meetingId);
                if (! $meeting || (int) $meeting->tenant_id !== (int) $tenantId) {
                    $v->errors()->add('meeting_id', __('documents.validation.meeting_must_belong_to_tenant'));
                    return;
                }

                if ($meeting && $boardId && (int) $meeting->board_id !== (int) $boardId) {
                    $v->errors()->add('board_id', __('documents.validation.board_must_match_meeting_board'));
                }
            }
        });

        $validated = $validator->validate();

        $document = new Document;
        $document->fill(Arr::only($validated, ['tenant_id', 'board_id', 'meeting_id', 'title', 'description', 'category', 'status']));
        $document->uploaded_by = $actor->getKey();

        // Se a meeting foi informada sem board, alinhar automaticamente ao board da meeting.
        if (filled($validated['meeting_id'] ?? null) && blank($validated['board_id'] ?? null)) {
            $meeting = Meeting::query()->find((int) $validated['meeting_id']);
            $document->board_id = $meeting?->board_id;
        }

        $document->save();

        return $document->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Document $document, array $data): Document
    {
        $data = $this->applyTenantGuard($actor, $data, $document);

        $validator = Validator::make(
            $data,
            [
                'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
                'board_id' => ['nullable', 'integer', 'exists:boards,id'],
                'meeting_id' => ['nullable', 'integer', 'exists:meetings,id'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'category' => ['nullable', 'string', 'max:255'],
                'status' => ['required', 'string', Rule::in(array_map(fn (DocumentStatus $s) => $s->value, DocumentStatus::cases()))],
            ],
            [],
            [
                'tenant_id' => __('documents.validation.attributes.tenant'),
                'board_id' => __('documents.validation.attributes.board'),
                'meeting_id' => __('documents.validation.attributes.meeting'),
                'title' => __('documents.validation.attributes.title'),
                'description' => __('documents.validation.attributes.description'),
                'category' => __('documents.validation.attributes.category'),
                'status' => __('documents.validation.attributes.status'),
            ],
        );

        $validator->after(function (\Illuminate\Validation\Validator $v) use ($document): void {
            $safe = $v->safe();
            $tenantId = $safe->tenant_id ?? null;
            $boardId = $safe->board_id ?? null;
            $meetingId = $safe->meeting_id ?? null;
            if (! $tenantId) {
                return;
            }

            $board = $boardId ? Board::query()->find($boardId) : null;
            $board = $boardId ? Board::query()->withoutGlobalScopes()->find($boardId) : null;
            if ($boardId && (! $board || (int) $board->tenant_id !== (int) $tenantId)) {
                $v->errors()->add('board_id', __('documents.validation.board_must_belong_to_tenant'));
            }

            $meeting = $meetingId ? Meeting::query()->withoutGlobalScopes()->find($meetingId) : null;
            if ($meetingId && (! $meeting || (int) $meeting->tenant_id !== (int) $tenantId)) {
                $v->errors()->add('meeting_id', __('documents.validation.meeting_must_belong_to_tenant'));
                return;
            }

            if ($meeting && $boardId && (int) $meeting->board_id !== (int) $boardId) {
                $v->errors()->add('board_id', __('documents.validation.board_must_match_meeting_board'));
            }

            if (! auth()->user()?->isSuperAdmin() && (int) $document->tenant_id !== (int) $tenantId) {
                $v->errors()->add('tenant_id', __('documents.validation.tenant_mismatch'));
            }
        });

        $validated = $validator->validate();

        $document->fill(Arr::only($validated, ['tenant_id', 'board_id', 'meeting_id', 'title', 'description', 'category', 'status']));

        if (filled($validated['meeting_id'] ?? null) && blank($validated['board_id'] ?? null)) {
            $meeting = Meeting::query()->find((int) $validated['meeting_id']);
            $document->board_id = $meeting?->board_id;
        }

        $document->save();

        return $document->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyTenantGuard(User $actor, array $data, ?Document $existing): array
    {
        if (! $actor->isSuperAdmin()) {
            $data['tenant_id'] = $actor->tenant_id;
        }

        if (! $actor->isSuperAdmin() && $actor->tenant_id === null) {
            throw ValidationException::withMessages([
                'tenant_id' => __('documents.validation.tenant_required'),
            ]);
        }

        if ($existing && ! $actor->isSuperAdmin()) {
            if ((int) $existing->tenant_id !== (int) $actor->tenant_id) {
                throw ValidationException::withMessages([
                    'tenant_id' => __('documents.validation.tenant_mismatch'),
                ]);
            }
        }

        return $data;
    }
}

