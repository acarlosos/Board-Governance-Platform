<?php

namespace App\Actions\Votes;

use App\Enums\VoteStatus;
use App\Enums\VoteType;
use App\Models\Meeting;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PersistVoteAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Vote
    {
        $data = $this->applyTenantGuard($actor, $data, null);

        $validator = Validator::make($data, [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'meeting_id' => ['required', 'integer', 'exists:meetings,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', Rule::in(array_map(fn (VoteType $t) => $t->value, VoteType::cases()))],
            'status' => ['required', 'string', Rule::in(array_map(fn (VoteStatus $s) => $s->value, VoteStatus::cases()))],
            'quorum_required' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            $safe = $v->safe();
            $tenantId = $safe->tenant_id ?? null;
            $meetingId = $safe->meeting_id ?? null;
            if (! $tenantId || ! $meetingId) {
                return;
            }

            $meeting = Meeting::query()->withoutGlobalScopes()->find($meetingId); // reason: validar meeting por id do payload; tenant verificado no fluxo da action.
            if (! $meeting || (int) $meeting->tenant_id !== (int) $tenantId) {
                $v->errors()->add('meeting_id', __('votes.validation.meeting_must_belong_to_tenant'));
            }
        });

        $validated = $validator->validate();

        $vote = new Vote;
        $vote->fill(Arr::only($validated, [
            'tenant_id',
            'meeting_id',
            'title',
            'description',
            'type',
            'status',
            'quorum_required',
            'starts_at',
            'ends_at',
        ]));
        $vote->created_by = $actor->getKey();
        $vote->save();

        return $vote->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Vote $vote, array $data): Vote
    {
        $data = $this->applyTenantGuard($actor, $data, $vote);

        if ($vote->status !== VoteStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => __('votes.validation.edit_only_in_draft'),
            ]);
        }

        $validator = Validator::make($data, [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'meeting_id' => ['required', 'integer', 'exists:meetings,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', Rule::in(array_map(fn (VoteType $t) => $t->value, VoteType::cases()))],
            'quorum_required' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $validator->after(function (\Illuminate\Validation\Validator $v) use ($vote): void {
            $safe = $v->safe();
            $tenantId = $safe->tenant_id ?? null;
            $meetingId = $safe->meeting_id ?? null;
            if (! $tenantId || ! $meetingId) {
                return;
            }

            $meeting = Meeting::query()->withoutGlobalScopes()->find($meetingId); // reason: validar meeting por id do payload; tenant verificado no fluxo da action.
            if (! $meeting || (int) $meeting->tenant_id !== (int) $tenantId) {
                $v->errors()->add('meeting_id', __('votes.validation.meeting_must_belong_to_tenant'));
            }

            if (! auth()->user()?->isSuperAdmin() && (int) $vote->tenant_id !== (int) $tenantId) {
                $v->errors()->add('tenant_id', __('votes.validation.tenant_mismatch'));
            }
        });

        $validated = $validator->validate();

        $vote->fill(Arr::only($validated, [
            'tenant_id',
            'meeting_id',
            'title',
            'description',
            'type',
            'quorum_required',
            'starts_at',
            'ends_at',
        ]));
        $vote->save();

        return $vote->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyTenantGuard(User $actor, array $data, ?Vote $existing): array
    {
        if (! $actor->isSuperAdmin()) {
            $data['tenant_id'] = $actor->tenant_id;
        }

        if (! $actor->isSuperAdmin() && $actor->tenant_id === null) {
            throw ValidationException::withMessages([
                'tenant_id' => __('votes.validation.tenant_required'),
            ]);
        }

        if ($existing && ! $actor->isSuperAdmin()) {
            if ((int) $existing->tenant_id !== (int) $actor->tenant_id) {
                throw ValidationException::withMessages([
                    'tenant_id' => __('votes.validation.tenant_mismatch'),
                ]);
            }
        }

        return $data;
    }
}

