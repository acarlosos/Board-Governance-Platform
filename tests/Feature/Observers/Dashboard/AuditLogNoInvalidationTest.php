<?php

namespace Tests\Feature\Observers\Dashboard;

use App\Enums\AuditAction;
use App\Enums\DashboardMetricsPeriod;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheKeys;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AuditLogNoInvalidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
    }

    #[Test]
    public function test_audit_logger_nao_invalida_cache_dashboard(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('tenant_admin');

        $task = Task::factory()->create(['tenant_id' => $tenant->id]);

        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);
        Cache::put($key, 'SENTINEL', 600);

        app(AuditLoggerService::class)->log(
            action: AuditAction::Updated,
            auditable: $task,
            oldValues: ['title' => 'a'],
            newValues: ['title' => 'b'],
            actor: $actor,
            tenantId: $tenant->id,
            request: Request::create('/test', 'GET'),
        );

        $this->assertSame('SENTINEL', Cache::get($key));
    }
}
