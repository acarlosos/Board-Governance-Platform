<?php

namespace App\Actions\Meetings;

use App\Enums\MeetingParticipantRole;
use App\Enums\MeetingParticipantStatus;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PersistMeetingParticipantAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, Meeting $meeting, array $data): MeetingParticipant
    {
        $this->assertTenantAccess($actor, $meeting);

        $data['tenant_id'] = $meeting->tenant_id;
        $data['meeting_id'] = $meeting->getKey();

        $validator = Validator::make(
            $data,
            [
                'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
                'meeting_id' => ['required', 'integer', 'exists:meetings,id'],
                'user_id' => ['required', 'integer', 'exists:users,id'],
                'role' => ['required', 'string', Rule::in(array_map(fn (MeetingParticipantRole $r) => $r->value, MeetingParticipantRole::cases()))],
                'status' => ['required', 'string', Rule::in(array_map(fn (MeetingParticipantStatus $s) => $s->value, MeetingParticipantStatus::cases()))],
                'responded_at' => ['nullable', 'date'],
            ],
            [],
            [
                'user_id' => __('meeting-participants.validation.attributes.user'),
                'role' => __('meeting-participants.validation.attributes.role'),
                'status' => __('meeting-participants.validation.attributes.status'),
                'responded_at' => __('meeting-participants.validation.attributes.responded_at'),
            ],
        );

        $validator->after(function (\Illuminate\Validation\Validator $v) use ($meeting): void {
            $userId = $v->safe()->user_id ?? null;
            if (! $userId) {
                return;
            }

            $user = \App\Models\User::query()->find($userId);
            if (! $user) {
                return;
            }

            if ((int) $user->tenant_id !== (int) $meeting->tenant_id) {
                $v->errors()->add('user_id', __('meeting-participants.validation.user_must_belong_to_meeting_tenant'));
            }

            $existsActive = MeetingParticipant::query()
                ->where('tenant_id', $meeting->tenant_id)
                ->where('meeting_id', $meeting->id)
                ->where('user_id', $userId)
                ->whereIn('status', [
                    MeetingParticipantStatus::Invited->value,
                    MeetingParticipantStatus::Confirmed->value,
                ])
                ->exists();

            if ($existsActive) {
                $v->errors()->add('user_id', __('meeting-participants.validation.duplicate_active_participant'));
            }
        });

        $validated = $validator->validate();

        $existing = MeetingParticipant::withTrashed()
            ->where('tenant_id', $meeting->tenant_id)
            ->where('meeting_id', $meeting->id)
            ->where('user_id', $validated['user_id'])
            ->first();

        if ($existing) {
            $existing->restore();
            $existing->fill(Arr::only($validated, ['role', 'status', 'responded_at']));
            $existing->save();

            return $existing->fresh();
        }

        $participant = new MeetingParticipant;
        $participant->fill(Arr::only($validated, ['tenant_id', 'meeting_id', 'user_id', 'role', 'status', 'responded_at']));
        $participant->save();

        return $participant->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, MeetingParticipant $participant, array $data): MeetingParticipant
    {
        $this->assertTenantAccess($actor, $participant->meeting);

        $data['tenant_id'] = $participant->tenant_id;
        $data['meeting_id'] = $participant->meeting_id;
        $data['user_id'] = $participant->user_id;

        $validator = Validator::make(
            $data,
            [
                'role' => ['required', 'string', Rule::in(array_map(fn (MeetingParticipantRole $r) => $r->value, MeetingParticipantRole::cases()))],
                'status' => ['required', 'string', Rule::in(array_map(fn (MeetingParticipantStatus $s) => $s->value, MeetingParticipantStatus::cases()))],
                'responded_at' => ['nullable', 'date'],
            ],
            [],
            [
                'role' => __('meeting-participants.validation.attributes.role'),
                'status' => __('meeting-participants.validation.attributes.status'),
                'responded_at' => __('meeting-participants.validation.attributes.responded_at'),
            ],
        );

        $validator->after(function (\Illuminate\Validation\Validator $v) use ($participant): void {
            $status = (string) ($v->safe()->status ?? '');
            if (! in_array($status, [MeetingParticipantStatus::Invited->value, MeetingParticipantStatus::Confirmed->value], true)) {
                return;
            }

            $existsActive = MeetingParticipant::query()
                ->where('tenant_id', $participant->tenant_id)
                ->where('meeting_id', $participant->meeting_id)
                ->where('user_id', $participant->user_id)
                ->whereIn('status', [
                    MeetingParticipantStatus::Invited->value,
                    MeetingParticipantStatus::Confirmed->value,
                ])
                ->whereKeyNot($participant->getKey())
                ->exists();

            if ($existsActive) {
                $v->errors()->add('status', __('meeting-participants.validation.duplicate_active_participant'));
            }
        });

        $validated = $validator->validate();

        $participant->fill(Arr::only($validated, ['role', 'status', 'responded_at']));
        $participant->save();

        return $participant->fresh();
    }

    public function remove(User $actor, MeetingParticipant $participant): void
    {
        $this->assertTenantAccess($actor, $participant->meeting);

        $participant->delete();
    }

    private function assertTenantAccess(User $actor, Meeting $meeting): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->tenant_id === null || (int) $actor->tenant_id !== (int) $meeting->tenant_id) {
            throw ValidationException::withMessages([
                'tenant_id' => __('meetings.validation.tenant_mismatch'),
            ]);
        }
    }
}

