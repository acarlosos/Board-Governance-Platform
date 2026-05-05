<?php

namespace Database\Factories;

use App\Enums\MeetingStatus;
use App\Models\Board;
use App\Models\Meeting;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
{
    protected $model = Meeting::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'board_id' => Board::factory()->state(fn (array $attributes): array => [
                'tenant_id' => $attributes['tenant_id'],
            ]),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'scheduled_at' => now()->addDays(2),
            'starts_at' => null,
            'ends_at' => null,
            'video_conference_url' => null,
            'status' => MeetingStatus::Draft,
            'created_by' => null,
        ];
    }
}

