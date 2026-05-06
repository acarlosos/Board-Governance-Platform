<?php

namespace Database\Factories;

use App\Enums\AuthSessionStatus;
use App\Models\AuthSession;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuthSession>
 */
class AuthSessionFactory extends Factory
{
    protected $model = AuthSession::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory()->state(fn (array $attributes): array => [
                'tenant_id' => $attributes['tenant_id'],
            ]),
            'session_id' => (string) Str::uuid(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'login_at' => now(),
            'logout_at' => null,
            'last_activity_at' => now(),
            'status' => AuthSessionStatus::Active,
            'created_at' => now(),
        ];
    }
}
