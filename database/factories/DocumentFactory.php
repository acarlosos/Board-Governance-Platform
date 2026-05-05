<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Board;
use App\Models\Document;
use App\Models\Meeting;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'board_id' => null,
            'meeting_id' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'category' => fake()->optional()->word(),
            'status' => DocumentStatus::Draft,
            'current_version_id' => null,
            'uploaded_by' => null,
        ];
    }
}

