<?php

namespace Database\Factories;

use App\Enums\BoardMemberRole;
use App\Enums\BoardMemberStatus;
use App\Models\Board;
use App\Models\BoardMember;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoardMember>
 */
class BoardMemberFactory extends Factory
{
    protected $model = BoardMember::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'board_id' => Board::factory()->state(fn (array $attributes): array => [
                'tenant_id' => $attributes['tenant_id'],
            ]),
            'user_id' => User::factory()->state(fn (array $attributes): array => [
                'tenant_id' => $attributes['tenant_id'],
            ]),
            'role' => BoardMemberRole::Member,
            'status' => BoardMemberStatus::Active,
            'joined_at' => null,
            'left_at' => null,
        ];
    }
}

