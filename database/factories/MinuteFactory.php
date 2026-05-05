<?php

namespace Database\Factories;

use App\Enums\MinuteStatus;
use App\Models\Meeting;
use App\Models\Minute;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Minute>
 */
class MinuteFactory extends Factory
{
    protected $model = Minute::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'meeting_id' => Meeting::factory()->state(fn (array $attributes): array => [
                'tenant_id' => $attributes['tenant_id'],
            ]),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraph(),
            'status' => MinuteStatus::Draft,
            'current_version_id' => null,
            'created_by' => null,
        ];
    }
}

