<?php

namespace App\Http\Requests\Api\V1\Tasks;

use App\Enums\TaskPriority;
use App\Models\Document;
use App\Models\Meeting;
use App\Models\Minute;
use App\Models\Vote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTaskRequest extends FormRequest
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
        $user = $this->user();

        return [
            'tenant_id' => ['prohibited'],
            'created_by' => ['prohibited'],
            'completed_at' => ['prohibited'],
            'status' => ['prohibited'],
            'id' => ['prohibited'],

            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'assigned_to' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($user): void {
                    if ($user !== null && ! $user->isSuperAdmin() && $user->tenant_id !== null) {
                        $query->where('tenant_id', $user->tenant_id);
                    }
                }),
            ],
            'related_type' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in([Meeting::class, Document::class, Minute::class, Vote::class]),
            ],
            'related_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}

