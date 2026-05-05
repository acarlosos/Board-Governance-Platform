<?php

namespace App\Actions\Meetings;

use App\Enums\MeetingAgendaItemStatus;
use App\Models\Meeting;
use App\Models\MeetingAgendaItem;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PersistMeetingAgendaItemAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, Meeting $meeting, array $data): MeetingAgendaItem
    {
        $this->assertTenantAccess($actor, $meeting);

        $data['tenant_id'] = $meeting->tenant_id;
        $data['meeting_id'] = $meeting->getKey();

        $validator = Validator::make(
            $data,
            [
                'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
                'meeting_id' => ['required', 'integer', 'exists:meetings,id'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'order_column' => ['required', 'integer', 'min:0'],
                'status' => ['required', 'string', Rule::in(array_map(fn (MeetingAgendaItemStatus $s) => $s->value, MeetingAgendaItemStatus::cases()))],
            ],
            [],
            [
                'title' => __('meeting-agenda-items.validation.attributes.title'),
                'description' => __('meeting-agenda-items.validation.attributes.description'),
                'order_column' => __('meeting-agenda-items.validation.attributes.order_column'),
                'status' => __('meeting-agenda-items.validation.attributes.status'),
            ],
        );

        $validated = $validator->validate();

        $item = new MeetingAgendaItem;
        $item->fill(Arr::only($validated, ['tenant_id', 'meeting_id', 'title', 'description', 'order_column', 'status']));
        $item->save();

        return $item->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, MeetingAgendaItem $item, array $data): MeetingAgendaItem
    {
        $this->assertTenantAccess($actor, $item->meeting);

        $data['tenant_id'] = $item->tenant_id;
        $data['meeting_id'] = $item->meeting_id;

        $validator = Validator::make(
            $data,
            [
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'order_column' => ['required', 'integer', 'min:0'],
                'status' => ['required', 'string', Rule::in(array_map(fn (MeetingAgendaItemStatus $s) => $s->value, MeetingAgendaItemStatus::cases()))],
            ],
            [],
            [
                'title' => __('meeting-agenda-items.validation.attributes.title'),
                'description' => __('meeting-agenda-items.validation.attributes.description'),
                'order_column' => __('meeting-agenda-items.validation.attributes.order_column'),
                'status' => __('meeting-agenda-items.validation.attributes.status'),
            ],
        );

        $validated = $validator->validate();

        $item->fill(Arr::only($validated, ['title', 'description', 'order_column', 'status']));
        $item->save();

        return $item->fresh();
    }

    public function remove(User $actor, MeetingAgendaItem $item): void
    {
        $this->assertTenantAccess($actor, $item->meeting);

        $item->delete();
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

