<?php

namespace Database\Factories;

use App\Enums\MeetingParticipantRole;
use App\Enums\MeetingParticipantStatus;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingParticipant>
 */
class MeetingParticipantFactory extends Factory
{
    protected $model = MeetingParticipant::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'meeting_id' => Meeting::factory()->state(fn (array $attributes): array => [
                'tenant_id' => $attributes['tenant_id'],
            ]),
            'user_id' => User::factory()->state(fn (array $attributes): array => [
                'tenant_id' => $attributes['tenant_id'],
            ]),
            'role' => MeetingParticipantRole::Participant,
            'status' => MeetingParticipantStatus::Invited,
            'responded_at' => null,
        ];
    }
}

