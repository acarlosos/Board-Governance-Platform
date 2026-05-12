<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantDashboardSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantDashboardSnapshot>
 */
class TenantDashboardSnapshotFactory extends Factory
{
    protected $model = TenantDashboardSnapshot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'period' => 'this_month',
            'payload' => [
                'version' => 'v1',
                'hero' => [],
                'operations' => [],
            ],
            'is_stale' => false,
            'refreshed_at' => now(),
        ];
    }
}
