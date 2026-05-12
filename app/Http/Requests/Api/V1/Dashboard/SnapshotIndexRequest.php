<?php

namespace App\Http\Requests\Api\V1\Dashboard;

use App\Enums\DashboardMetricsPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class SnapshotIndexRequest extends FormRequest
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
            'period' => ['nullable', 'string', new Enum(DashboardMetricsPeriod::class)],
        ];
    }
}
