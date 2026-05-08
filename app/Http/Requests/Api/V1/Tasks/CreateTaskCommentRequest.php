<?php

namespace App\Http\Requests\Api\V1\Tasks;

use Illuminate\Foundation\Http\FormRequest;

final class CreateTaskCommentRequest extends FormRequest
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
            'tenant_id' => ['prohibited'],
            'id' => ['prohibited'],
            'task_id' => ['prohibited'],
            'user_id' => ['prohibited'],

            'comment' => ['required', 'string', 'max:5000'],
        ];
    }
}

