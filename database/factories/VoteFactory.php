<?php

namespace Database\Factories;

use App\Enums\VoteStatus;
use App\Enums\VoteType;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
{
    protected $model = Vote::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'meeting_id' => Meeting::factory()->state(fn (array $attributes): array => [
                'tenant_id' => $attributes['tenant_id'],
            ]),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'type' => VoteType::Open,
            'status' => VoteStatus::Draft,
            'quorum_required' => null,
            'starts_at' => null,
            'ends_at' => null,
            'created_by' => null,
        ];
    }
}

