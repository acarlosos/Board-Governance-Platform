<?php

namespace App\Actions\Minutes;

use App\Enums\MinuteStatus;
use App\Models\Meeting;
use App\Models\Minute;
use App\Models\User;
use App\Support\Minutes\MinuteContent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PersistMinuteAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Minute
    {
        $data = $this->applyTenantGuard($actor, $data, null);

        $validator = Validator::make(
            $data,
            [
                'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
                'meeting_id' => ['required', 'integer', 'exists:meetings,id'],
                'title' => ['required', 'string', 'max:255'],
                'content' => ['required', 'string'],
                'status' => ['required', 'string', Rule::in(array_map(fn (MinuteStatus $s) => $s->value, MinuteStatus::cases()))],
            ],
        );

        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            $safe = $v->safe();
            $tenantId = $safe->tenant_id ?? null;
            $meetingId = $safe->meeting_id ?? null;

            if (MinuteContent::isBlank($safe->content ?? null)) {
                $v->errors()->add('content', __('minutes.validation.content_required'));
            }

            if (! $tenantId || ! $meetingId) {
                return;
            }

            $meeting = Meeting::query()->withoutGlobalScopes()->find($meetingId); // reason: meeting do payload; tenant verificado na action.
            if (! $meeting || (int) $meeting->tenant_id !== (int) $tenantId) {
                $v->errors()->add('meeting_id', __('minutes.validation.meeting_must_belong_to_tenant'));
            }
        });

        $validated = $validator->validate();

        $minute = new Minute;
        $minute->fill(Arr::only($validated, ['tenant_id', 'meeting_id', 'title', 'content', 'status']));
        $minute->created_by = $actor->getKey();
        $minute->save();

        return $minute->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Minute $minute, array $data): Minute
    {
        $data = $this->applyTenantGuard($actor, $data, $minute);

        if ($minute->status !== MinuteStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => __('minutes.validation.edit_only_in_draft'),
            ]);
        }

        $validator = Validator::make(
            $data,
            [
                'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
                'meeting_id' => ['required', 'integer', 'exists:meetings,id'],
                'title' => ['required', 'string', 'max:255'],
                'content' => ['required', 'string'],
            ],
        );

        $validator->after(function (\Illuminate\Validation\Validator $v) use ($minute): void {
            $safe = $v->safe();
            $tenantId = $safe->tenant_id ?? null;
            $meetingId = $safe->meeting_id ?? null;

            if (MinuteContent::isBlank($safe->content ?? null)) {
                $v->errors()->add('content', __('minutes.validation.content_required'));
            }

            if (! $tenantId || ! $meetingId) {
                return;
            }

            $meeting = Meeting::query()->withoutGlobalScopes()->find($meetingId); // reason: meeting do payload; tenant verificado na action.
            if (! $meeting || (int) $meeting->tenant_id !== (int) $tenantId) {
                $v->errors()->add('meeting_id', __('minutes.validation.meeting_must_belong_to_tenant'));
            }

            if (! auth()->user()?->isSuperAdmin() && (int) $minute->tenant_id !== (int) $tenantId) {
                $v->errors()->add('tenant_id', __('minutes.validation.tenant_mismatch'));
            }
        });

        $validated = $validator->validate();

        $minute->fill(Arr::only($validated, ['tenant_id', 'meeting_id', 'title', 'content']));
        $minute->save();

        return $minute->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyTenantGuard(User $actor, array $data, ?Minute $existing): array
    {
        if (! $actor->isSuperAdmin()) {
            $data['tenant_id'] = $actor->tenant_id;
        }

        if (! $actor->isSuperAdmin() && $actor->tenant_id === null) {
            throw ValidationException::withMessages([
                'tenant_id' => __('minutes.validation.tenant_required'),
            ]);
        }

        if ($existing && ! $actor->isSuperAdmin()) {
            if ((int) $existing->tenant_id !== (int) $actor->tenant_id) {
                throw ValidationException::withMessages([
                    'tenant_id' => __('minutes.validation.tenant_mismatch'),
                ]);
            }
        }

        return $data;
    }
}
