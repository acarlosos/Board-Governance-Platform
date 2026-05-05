<?php

namespace Database\Factories;

use App\Enums\BoardStatus;
use App\Models\Board;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Board>
 */
class BoardFactory extends Factory
{
    protected $model = Board::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->company().' Board',
            'description' => fake()->optional()->sentence(),
            'status' => BoardStatus::Active,
            'created_by' => null,
        ];
    }
}

