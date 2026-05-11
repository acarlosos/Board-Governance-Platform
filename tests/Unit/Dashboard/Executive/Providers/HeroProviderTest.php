<?php

namespace Tests\Unit\Dashboard\Executive\Providers;

use App\Enums\DashboardMetricsPeriod;
use App\Enums\MeetingStatus;
use App\Enums\TaskStatus;
use App\Enums\VoteStatus;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vote;
use App\Services\Dashboard\Executive\Providers\HeroProvider;
use Database\Seeders\RolesAndPermissionsSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class HeroProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function test_tenant_vazio_entrega_zeros_e_sem_proxima_reuniao(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $hero = (new HeroProvider)->build($admin, DashboardMetricsPeriod::AllTime);

        $this->assertSame(0, $hero->tasksOverdue);
        $this->assertSame(0, $hero->votesOpen);
        $this->assertSame(0, $hero->signaturesPending);
        $this->assertNull($hero->nextMeetingAt);
        $this->assertNull($hero->nextMeetingId);
    }

    #[Test]
    public function test_tenant_a_nao_ve_counts_de_task_overdue_do_tenant_b(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        Task::factory()->create([
            'tenant_id' => $tenantB->id,
            'status' => TaskStatus::Pending,
            'due_date' => now()->subDay(),
        ]);

        $admin = User::factory()->create(['tenant_id' => $tenantA->id]);
        $admin->assignRole('tenant_admin');

        $hero = (new HeroProvider)->build($admin, DashboardMetricsPeriod::AllTime);

        $this->assertSame(0, $hero->tasksOverdue);
    }

    #[Test]
    public function test_super_admin_global_agrega_entre_tenants_para_overdue_tasks(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        Task::factory()->create([
            'tenant_id' => $tenantA->id,
            'status' => TaskStatus::InProgress,
            'due_date' => now()->subDay(),
        ]);
        Task::factory()->create([
            'tenant_id' => $tenantB->id,
            'status' => TaskStatus::Pending,
            'due_date' => now()->subHours(3),
        ]);

        $global = User::factory()->create([
            'tenant_id' => null,
            'is_super_admin' => true,
        ]);

        $hero = (new HeroProvider)->build($global, DashboardMetricsPeriod::AllTime);

        $this->assertSame(2, $hero->tasksOverdue);
    }

    #[Test]
    public function test_proxima_reuniao_no_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        Meeting::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => MeetingStatus::Scheduled,
            'scheduled_at' => now()->addDays(5),
        ]);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $hero = (new HeroProvider)->build($admin, DashboardMetricsPeriod::AllTime);

        $this->assertNotNull($hero->nextMeetingAt);
        $this->assertNotNull($hero->nextMeetingId);
    }

    #[Test]
    public function test_votes_open_conta_no_tenant_correto(): void
    {
        $tenant = Tenant::factory()->create();

        Vote::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
            'status' => VoteStatus::Open,
        ]);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $hero = (new HeroProvider)->build($admin, DashboardMetricsPeriod::AllTime);

        $this->assertSame(2, $hero->votesOpen);
    }
}
