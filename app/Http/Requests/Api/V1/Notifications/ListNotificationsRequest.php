<?php

namespace App\Http\Requests\Api\V1\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListNotificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'unread' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::enum(NotificationStatus::class)],
            'channel' => ['sometimes', Rule::enum(NotificationChannel::class)],
            'sort' => [
                'sometimes',
                'string',
                'max:32',
                Rule::in([
                    'created_at', '-created_at',
                    'read_at', '-read_at',
                    'sent_at', '-sent_at',
                ]),
            ],
        ];
    }
}
