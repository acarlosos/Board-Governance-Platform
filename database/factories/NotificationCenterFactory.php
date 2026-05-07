<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\NotificationCenter;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationCenter>
 */
class NotificationCenterFactory extends Factory
{
    protected $model = NotificationCenter::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(6),
            'body' => fake()->sentence(),
            'channel' => NotificationChannel::Database,
            'status' => NotificationStatus::Sent,
            'related_type' => null,
            'related_id' => null,
            'read_at' => null,
            'sent_at' => now(),
            'metadata' => [],
        ];
    }
}

