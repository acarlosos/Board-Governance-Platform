<?php

namespace App\Actions\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationTemplateStatus;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PersistNotificationTemplateAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): NotificationTemplate
    {
        $data = $this->applyTenantGuard($actor, $data, existing: null);

        $validated = $this->validate($data);

        $template = new NotificationTemplate;
        $template->fill(Arr::only($validated, [
            'tenant_id',
            'key',
            'name',
            'subject',
            'body',
            'locale',
            'channel',
            'status',
            'variables',
        ]));
        $template->created_by = $actor->id;
        $template->save();

        return $template->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, NotificationTemplate $template, array $data): NotificationTemplate
    {
        $data = $this->applyTenantGuard($actor, $data, existing: $template);

        $validated = $this->validate($data);

        $template->fill(Arr::only($validated, [
            'tenant_id',
            'key',
            'name',
            'subject',
            'body',
            'locale',
            'channel',
            'status',
            'variables',
        ]));
        $template->save();

        return $template->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return Validator::make($data, [
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'key' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'locale' => ['required', 'string', 'max:16'],
            'channel' => ['required', 'string', Rule::in(array_map(fn (NotificationChannel $c) => $c->value, NotificationChannel::cases()))],
            'status' => ['required', 'string', Rule::in(array_map(fn (NotificationTemplateStatus $s) => $s->value, NotificationTemplateStatus::cases()))],
            'variables' => ['nullable', 'array'],
        ])->validate();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyTenantGuard(User $actor, array $data, ?NotificationTemplate $existing): array
    {
        if (! $actor->isSuperAdmin()) {
            // tenant_admin nunca cria/edita template global: força tenant_id
            $data['tenant_id'] = $actor->tenant_id;
        }

        if (! $actor->isSuperAdmin() && $actor->tenant_id === null) {
            throw ValidationException::withMessages([
                'tenant_id' => __('notification-templates.validation.tenant_required'),
            ]);
        }

        if ($existing && ! $actor->isSuperAdmin()) {
            // não permitir editar global por tenant_admin
            if ($existing->tenant_id === null) {
                throw ValidationException::withMessages([
                    'tenant_id' => __('notification-templates.validation.cannot_edit_global'),
                ]);
            }

            if ((int) $existing->tenant_id !== (int) $actor->tenant_id) {
                throw ValidationException::withMessages([
                    'tenant_id' => __('notification-templates.validation.tenant_mismatch'),
                ]);
            }
        }

        return $data;
    }
}

