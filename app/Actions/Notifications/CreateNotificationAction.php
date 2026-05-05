<?php

namespace App\Actions\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\NotificationCenter;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\Notifications\NotificationTemplateRenderer;
use App\Services\Notifications\NotificationTemplateResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class CreateNotificationAction
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $templateData
     */
    public function create(User $actor, array $data, array $templateData = []): NotificationCenter
    {
        $data = $this->applyTenantGuard($actor, $data);

        $validated = $this->validate($data);

        $tenantId = (int) $validated['tenant_id'];
        $channel = NotificationChannel::from((string) $validated['channel']);

        $template = null;
        if (! empty($validated['template_key'])) {
            $locale = (string) ($validated['locale'] ?? 'pt_BR');
            $template = app(NotificationTemplateResolver::class)->resolve($tenantId, (string) $validated['template_key'], $locale, $channel);
        }

        $title = (string) $validated['title'];
        $body = $validated['body'] ?? null;

        if ($template) {
            $rendered = app(NotificationTemplateRenderer::class)->render($template, $templateData);
            if ($rendered['subject'] !== '') {
                $title = $rendered['subject'];
            }
            $body = $rendered['body'] !== '' ? $rendered['body'] : $body;
        }

        $notification = NotificationCenter::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => (int) $validated['user_id'],
            'title' => $title,
            'body' => $body,
            'channel' => $channel->value,
            'status' => NotificationStatus::Unread->value,
            'related_type' => $validated['related_type'] ?? null,
            'related_id' => $validated['related_id'] ?? null,
            'metadata' => $this->sanitizeMetadata((array) ($validated['metadata'] ?? [])),
        ]);

        return $notification->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        $validator = Validator::make($data, [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'channel' => ['required', 'string', Rule::in(array_map(fn (NotificationChannel $c) => $c->value, NotificationChannel::cases()))],
            'status' => ['nullable', 'string'],
            'template_key' => ['nullable', 'string', 'max:128'],
            'locale' => ['nullable', 'string', 'max:16'],
            'related_type' => ['nullable', 'string'],
            'related_id' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
        ]);

        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            $safe = $v->safe();
            $tenantId = $safe->tenant_id ?? null;
            if (! $tenantId) {
                return;
            }

            $user = User::query()->withoutGlobalScopes()->find((int) $safe->user_id);
            if (! $user || (int) $user->tenant_id !== (int) $tenantId) {
                $v->errors()->add('user_id', __('notifications.validation.user_must_belong_to_tenant'));
            }

            $relatedType = $safe->related_type ?? null;
            $relatedId = $safe->related_id ?? null;
            if ($relatedType || $relatedId) {
                $related = $this->resolveRelated($relatedType, $relatedId);
                if (! $related || (int) $related->getAttribute('tenant_id') !== (int) $tenantId) {
                    $v->errors()->add('related_id', __('notifications.validation.related_must_belong_to_tenant'));
                }
            }
        });

        return $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyTenantGuard(User $actor, array $data): array
    {
        if (! $actor->isSuperAdmin()) {
            $data['tenant_id'] = $actor->tenant_id;
        }

        if (! $actor->isSuperAdmin() && $actor->tenant_id === null) {
            throw ValidationException::withMessages([
                'tenant_id' => __('notifications.validation.tenant_required'),
            ]);
        }

        return $data;
    }

    private function resolveRelated(?string $relatedType, mixed $relatedId): ?Model
    {
        if (! $relatedType || ! $relatedId) {
            return null;
        }

        /** @var class-string<Model> $relatedType */
        if (! class_exists($relatedType)) {
            return null;
        }

        // lookup interno sem scope, mas sempre validando tenant_id depois
        return $relatedType::query()->withoutGlobalScopes()->find((int) $relatedId);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        $sensitiveKeys = [
            'password',
            'client_secret',
            'private_key',
            'token',
            'access_token',
            'refresh_token',
            'secret',
            'api_key',
            'body',
            'subject',
            'payload',
        ];

        foreach ($sensitiveKeys as $key) {
            Arr::forget($metadata, $key);
        }

        return $metadata;
    }
}

