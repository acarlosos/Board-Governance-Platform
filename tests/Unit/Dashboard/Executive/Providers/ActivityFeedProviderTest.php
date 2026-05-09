<?php

namespace Tests\Unit\Dashboard\Executive\Providers;

use App\Enums\DashboardMetricsPeriod;
use App\Models\AuditLog;
use App\Models\Minute;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Dashboard\Executive\Providers\ActivityFeedProvider;
use Database\Seeders\RolesAndPermissionsSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ActivityFeedProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function test_d4_super_admin_global_entrega_feed_vazio(): void
    {
        $tenant = Tenant::factory()->create();
        $minute = Minute::factory()->create(['tenant_id' => $tenant->id]);

        AuditLog::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => null,
            'action' => 'minute.created',
            'auditable_type' => Minute::class,
            'auditable_id' => $minute->id,
            'old_values' => null,
            'new_values' => null,
            'created_at' => now(),
        ]);

        $global = User::factory()->create([
            'tenant_id' => null,
            'is_super_admin' => true,
        ]);

        $items = (new ActivityFeedProvider)->build($global, DashboardMetricsPeriod::AllTime);

        $this->assertSame([], $items);
    }

    #[Test]
    public function test_board_member_sem_view_any_recebe_lista_vazia(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);
        $actor->assignRole('board_member');

        AuditLog::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $actor->id,
            'action' => 'tenant.updated',
            'auditable_type' => Tenant::class,
            'auditable_id' => $tenant->id,
            'old_values' => null,
            'new_values' => null,
            'created_at' => now(),
        ]);

        $items = (new ActivityFeedProvider)->build($actor, DashboardMetricsPeriod::AllTime);

        $this->assertSame([], $items);
    }

    #[Test]
    public function test_take_respeita_activity_max(): void
    {
        $tenant = Tenant::factory()->create();
        $minute = Minute::factory()->create(['tenant_id' => $tenant->id]);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        for ($i = 0; $i < 20; $i++) {
            AuditLog::query()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $admin->id,
                'action' => "minute.touch.{$i}",
                'auditable_type' => Minute::class,
                'auditable_id' => $minute->id,
                'old_values' => null,
                'new_values' => null,
                'created_at' => now()->subSeconds($i),
            ]);
        }

        $items = (new ActivityFeedProvider)->build($admin, DashboardMetricsPeriod::AllTime);

        $this->assertCount(15, $items);
    }

    #[Test]
    public function test_auditable_inexistente_gera_item_com_resource_id_nulo(): void
    {
        $tenant = Tenant::factory()->create();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        AuditLog::query()->delete();

        $probe = 'dashboard.activity.orphan_probe';

        AuditLog::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'action' => $probe,
            'auditable_type' => Minute::class,
            'auditable_id' => 9_999_999,
            'old_values' => null,
            'new_values' => null,
            'created_at' => now(),
        ]);

        $items = (new ActivityFeedProvider)->build($admin, DashboardMetricsPeriod::AllTime);

        $onlyProbe = array_values(array_filter(
            $items,
            static fn ($i) => $i->summary === $probe,
        ));

        $this->assertCount(1, $onlyProbe);
        $this->assertNull($onlyProbe[0]->resourceId);
    }

    #[Test]
    public function test_audit_logs_de_outro_tenant_nunca_aparecem(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $minuteB = Minute::factory()->create(['tenant_id' => $tenantB->id]);

        $admin = User::factory()->create(['tenant_id' => $tenantA->id]);
        $admin->assignRole('tenant_admin');

        AuditLog::query()->delete();

        AuditLog::query()->create([
            'tenant_id' => $tenantB->id,
            'user_id' => null,
            'action' => 'dashboard.activity.tenant_b_only',
            'auditable_type' => Minute::class,
            'auditable_id' => $minuteB->id,
            'old_values' => null,
            'new_values' => null,
            'created_at' => now(),
        ]);

        $items = (new ActivityFeedProvider)->build($admin, DashboardMetricsPeriod::AllTime);

        $this->assertSame([], $items);
    }
}
