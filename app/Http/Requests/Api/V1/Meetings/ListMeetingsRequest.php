<?php

namespace App\Http\Requests\Api\V1\Meetings;

use App\Enums\MeetingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListMeetingsRequest extends FormRequest
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
            'q' => ['sometimes', 'string', 'max:100'],
            'board_id' => [
                'sometimes',
                'integer',
                Rule::exists('boards', 'id')->where(function ($query): void {
                    $user = $this->user();
                    if ($user !== null && ! $user->isSuperAdmin() && $user->tenant_id !== null) {
                        $query->where('tenant_id', $user->tenant_id);
                    }
                }),
            ],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
            'status' => ['sometimes', Rule::enum(MeetingStatus::class)],
            'sort' => [
                'sometimes',
                'string',
                'max:32',
                Rule::in(['scheduled_at', '-scheduled_at', 'created_at', '-created_at']),
            ],
        ];
    }

    protected function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->filled('date_from') && $this->filled('date_to')) {
                $from = strtotime((string) $this->input('date_from'));
                $to = strtotime((string) $this->input('date_to'));
                if ($from !== false && $to !== false && $to < $from) {
                    $validator->errors()->add('date_to', __('validation.after_or_equal', ['attribute' => 'date_to', 'date' => 'date_from']));
                }
            }
        });
    }
}
