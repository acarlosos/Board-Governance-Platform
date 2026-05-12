<?php

namespace Tests\Feature\Dashboard\Executive\Observability;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Tenant;
use App\Services\Dashboard\Executive\Observability\ExecutiveDashboardObservability;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InvalidatorInstrumentationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function test_invalidate_por_task_created_incrementa_invalidations_uma_vez(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 10:00:00', config('app.timezone')));

        $tenant = Tenant::factory()->create();

        $before = app(ExecutiveDashboardObservability::class)->snapshotFor(CarbonImmutable::parse('2026-06-10', config('app.timezone')));
        $this->assertSame(0, $before['invalidations']);

        Task::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => TaskStatus::Pending,
        ]);

        $after = app(ExecutiveDashboardObservability::class)->snapshotFor(CarbonImmutable::parse('2026-06-10', config('app.timezone')));
        $this->assertSame(1, $after['invalidations']);

        CarbonImmutable::setTestNow();
    }
}
