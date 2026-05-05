<?php

namespace Database\Factories;

use App\Models\Minute;
use App\Models\MinuteVersion;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MinuteVersion>
 */
class MinuteVersionFactory extends Factory
{
    protected $model = MinuteVersion::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'minute_id' => Minute::factory()->state(fn (array $attributes): array => [
                'tenant_id' => $attributes['tenant_id'],
            ]),
            'version_number' => 1,
            'content' => fake()->paragraph(),
            'changes_summary' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }
}

