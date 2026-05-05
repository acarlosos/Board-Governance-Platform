<?php

namespace Database\Factories;

use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Models\Integration;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Integration>
 */
class IntegrationFactory extends Factory
{
    protected $model = Integration::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'type' => IntegrationType::Email,
            'provider' => IntegrationProvider::Smtp,
            'name' => fake()->company().' SMTP',
            'status' => IntegrationStatus::Inactive,
            'config' => [
                'host' => 'smtp.example.test',
                'port' => 587,
                'username' => 'user',
                'password' => 'secret',
                'encryption' => 'tls',
                'from_address' => 'noreply@example.test',
                'from_name' => 'Board',
            ],
            'last_tested_at' => null,
            'last_test_status' => null,
            'last_test_message' => null,
            'created_by' => null,
        ];
    }
}

