<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Vote;
use App\Models\VoteOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoteOption>
 */
class VoteOptionFactory extends Factory
{
    protected $model = VoteOption::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'vote_id' => Vote::factory()->state(fn (array $attributes): array => [
                'tenant_id' => $attributes['tenant_id'],
            ]),
            'title' => fake()->sentence(2),
            'description' => fake()->optional()->sentence(),
            'order_column' => 0,
        ];
    }
}

