<?php

namespace App\Http\Requests\Api\V1\Boards;

use App\Enums\BoardStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListBoardsRequest extends FormRequest
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
            'status' => ['sometimes', Rule::enum(BoardStatus::class)],
            'sort' => [
                'sometimes',
                'string',
                'max:32',
                Rule::in(['name', '-name', 'created_at', '-created_at']),
            ],
        ];
    }
}
