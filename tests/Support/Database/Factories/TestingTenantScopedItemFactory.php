<?php

namespace Tests\Support\Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Support\Models\TestingTenantScopedItem;

/**
 * @extends Factory<TestingTenantScopedItem>
 */
class TestingTenantScopedItemFactory extends Factory
{
    protected $model = TestingTenantScopedItem::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'label' => fake()->words(3, true),
        ];
    }
}
