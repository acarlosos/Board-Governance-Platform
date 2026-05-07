<?php

namespace App\Http\Requests\Api\V1\Tasks;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListTasksRequest extends FormRequest
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
            // 'me' ou um ID numérico (apenas para perfis com gestão no tenant; a Action reforça).
            'assigned_to' => ['sometimes', 'string', 'max:20'],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'sort' => [
                'sometimes',
                'string',
                'max:32',
                Rule::in([
                    'created_at', '-created_at',
                    'due_date', '-due_date',
                    'priority', '-priority',
                    'status', '-status',
                ]),
            ],
        ];
    }
}
