<?php

namespace Database\Factories;

use App\Enums\MeetingAgendaItemStatus;
use App\Models\Meeting;
use App\Models\MeetingAgendaItem;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingAgendaItem>
 */
class MeetingAgendaItemFactory extends Factory
{
    protected $model = MeetingAgendaItem::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'meeting_id' => Meeting::factory()->state(fn (array $attributes): array => [
                'tenant_id' => $attributes['tenant_id'],
            ]),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'order_column' => 0,
            'status' => MeetingAgendaItemStatus::Pending,
        ];
    }
}

