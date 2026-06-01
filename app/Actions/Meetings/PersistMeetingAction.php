<?php

namespace App\Actions\Meetings;

use App\Enums\MeetingStatus;
use App\Models\Board;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PersistMeetingAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Meeting
    {
        $data = $this->applyTenantGuard($actor, $data, null);

        $validator = Validator::make(
            $data,
            [
                'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
                'board_id' => ['required', 'integer', 'exists:boards,id'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'scheduled_at' => ['required', 'date'],
                'starts_at' => ['nullable', 'date'],
                'ends_at' => ['nullable', 'date'],
                'video_conference_url' => ['nullable', 'url', 'max:2048'],
                'status' => ['required', 'string', Rule::in(array_map(fn (MeetingStatus $s) => $s->value, MeetingStatus::cases()))],
            ],
            [],
            [
                'tenant_id' => __('meetings.validation.attributes.tenant'),
                'board_id' => __('meetings.validation.attributes.board'),
                'title' => __('meetings.validation.attributes.title'),
                'description' => __('meetings.validation.attributes.description'),
                'scheduled_at' => __('meetings.validation.attributes.scheduled_at'),
                'starts_at' => __('meetings.validation.attributes.starts_at'),
                'ends_at' => __('meetings.validation.attributes.ends_at'),
                'video_conference_url' => __('meetings.validation.attributes.video_conference_url'),
                'status' => __('meetings.validation.attributes.status'),
            ],
        );

        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            $safe = $v->safe();
            $tenantId = $safe->tenant_id ?? null;
            $boardId = $safe->board_id ?? null;
            if (! $tenantId || ! $boardId) {
                return;
            }

            $board = Board::query()->find($boardId);
            if (! $board) {
                return;
            }

            if ((int) $board->tenant_id !== (int) $tenantId) {
                $v->errors()->add('board_id', __('meetings.validation.board_must_belong_to_tenant'));
            }
        });

        $validated = $validator->validate();

        return DB::transaction(function () use ($actor, $validated): Meeting {
            $meeting = new Meeting;
            $meeting->fill(Arr::only($validated, [
                'tenant_id',
                'board_id',
                'title',
                'description',
                'scheduled_at',
                'starts_at',
                'ends_at',
                'video_conference_url',
                'status',
            ]));
            $meeting->created_by = $actor->getKey();
            $meeting->save();

            return app(SyncMeetingParticipantsFromBoardAction::class)->sync($actor, $meeting);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Meeting $meeting, array $data): Meeting
    {
        $data = $this->applyTenantGuard($actor, $data, $meeting);

        $validator = Validator::make(
            $data,
            [
                'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
                'board_id' => ['required', 'integer', 'exists:boards,id'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'scheduled_at' => ['required', 'date'],
                'starts_at' => ['nullable', 'date'],
                'ends_at' => ['nullable', 'date'],
                'video_conference_url' => ['nullable', 'url', 'max:2048'],
                'status' => ['required', 'string', Rule::in(array_map(fn (MeetingStatus $s) => $s->value, MeetingStatus::cases()))],
            ],
            [],
            [
                'tenant_id' => __('meetings.validation.attributes.tenant'),
                'board_id' => __('meetings.validation.attributes.board'),
                'title' => __('meetings.validation.attributes.title'),
                'description' => __('meetings.validation.attributes.description'),
                'scheduled_at' => __('meetings.validation.attributes.scheduled_at'),
                'starts_at' => __('meetings.validation.attributes.starts_at'),
                'ends_at' => __('meetings.validation.attributes.ends_at'),
                'video_conference_url' => __('meetings.validation.attributes.video_conference_url'),
                'status' => __('meetings.validation.attributes.status'),
            ],
        );

        $validator->after(function (\Illuminate\Validation\Validator $v) use ($meeting): void {
            $safe = $v->safe();
            $tenantId = $safe->tenant_id ?? null;
            $boardId = $safe->board_id ?? null;
            if (! $tenantId || ! $boardId) {
                return;
            }

            $board = Board::query()->find($boardId);
            if (! $board) {
                return;
            }

            if ((int) $board->tenant_id !== (int) $tenantId) {
                $v->errors()->add('board_id', __('meetings.validation.board_must_belong_to_tenant'));
            }

            if (! auth()->user()?->isSuperAdmin() && (int) $meeting->tenant_id !== (int) $tenantId) {
                $v->errors()->add('tenant_id', __('meetings.validation.tenant_mismatch'));
            }
        });

        $validated = $validator->validate();

        $meeting->fill(Arr::only($validated, [
            'tenant_id',
            'board_id',
            'title',
            'description',
            'scheduled_at',
            'starts_at',
            'ends_at',
            'video_conference_url',
            'status',
        ]));
        $meeting->save();

        return $meeting->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyTenantGuard(User $actor, array $data, ?Meeting $existing): array
    {
        if (! $actor->isSuperAdmin()) {
            $data['tenant_id'] = $actor->tenant_id;
        }

        if (! $actor->isSuperAdmin() && $actor->tenant_id === null) {
            throw ValidationException::withMessages([
                'tenant_id' => __('meetings.validation.tenant_required'),
            ]);
        }

        if ($existing && ! $actor->isSuperAdmin()) {
            if ((int) $existing->tenant_id !== (int) $actor->tenant_id) {
                throw ValidationException::withMessages([
                    'tenant_id' => __('meetings.validation.tenant_mismatch'),
                ]);
            }
        }

        return $data;
    }
}
