<?php

namespace Tests\Unit\Dashboard\Executive\Providers;

use App\Enums\DashboardMetricsPeriod;
use App\Enums\MinuteStatus;
use App\Enums\NotificationStatus;
use App\Models\Minute;
use App\Models\NotificationCenter;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Dashboard\Executive\Providers\OperationsProvider;
use Database\Seeders\RolesAndPermissionsSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class OperationsProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function test_tenant_vazio_entrega_zeros(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $block = (new OperationsProvider)->build($admin, DashboardMetricsPeriod::AllTime);

        $this->assertSame(0, $block->minutesPendingReview);
        $this->assertSame(0, $block->meetingsThisMonth);
        $this->assertSame(0, $block->notificationsUnread);
    }

    #[Test]
    public function test_tenant_a_nao_ve_minutes_do_tenant_b(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        Minute::factory()->create([
            'tenant_id' => $tenantB->id,
            'status' => MinuteStatus::InReview,
        ]);

        $admin = User::factory()->create(['tenant_id' => $tenantA->id]);
        $admin->assignRole('tenant_admin');

        $block = (new OperationsProvider)->build($admin, DashboardMetricsPeriod::AllTime);

        $this->assertSame(0, $block->minutesPendingReview);
    }

    #[Test]
    public function test_super_admin_global_soma_minutes_in_review(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        Minute::factory()->create([
            'tenant_id' => $tenantA->id,
            'status' => MinuteStatus::InReview,
        ]);
        Minute::factory()->create([
            'tenant_id' => $tenantB->id,
            'status' => MinuteStatus::InReview,
        ]);

        $global = User::factory()->create([
            'tenant_id' => null,
            'is_super_admin' => true,
        ]);

        $block = (new OperationsProvider)->build($global, DashboardMetricsPeriod::AllTime);

        $this->assertSame(2, $block->minutesPendingReview);
    }

    #[Test]
    public function test_notificacoes_nao_lidas_respeitam_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        NotificationCenter::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => NotificationStatus::Unread,
        ]);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $block = (new OperationsProvider)->build($admin, DashboardMetricsPeriod::AllTime);

        $this->assertSame(3, $block->notificationsUnread);
    }
}
