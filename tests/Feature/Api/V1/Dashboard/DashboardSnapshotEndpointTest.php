<?php

namespace Tests\Feature\Api\V1\Dashboard;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class DashboardSnapshotEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_snapshot_sem_bearer_retorna_401_unauthenticated(): void
    {
        $this->getJson('/api/v1/dashboard/snapshot')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_snapshot_token_sem_reports_read_retorna_403_forbidden_ability(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $token = $user->createToken('device', ['tasks:read']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/dashboard/snapshot')
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'forbidden_ability');
    }

    public function test_snapshot_sucesso_200_envelope_e_shape(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $token = $user->createToken('device', ['reports:read']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/dashboard/snapshot')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.api_version', 'v1')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'version',
                    'period',
                    'cache_segment',
                    'generated_at',
                    'hero',
                    'kpis',
                    'operations',
                    'priorities',
                    'activity',
                ],
                'meta' => ['request_id', 'api_version'],
            ]);
    }

    public function test_snapshot_period_last_30_days(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $token = $user->createToken('device', ['reports:read']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/dashboard/snapshot?period=last_30_days')
            ->assertOk()
            ->assertJsonPath('data.period', 'last_30_days');
    }

    public function test_snapshot_period_invalido_retorna_422(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $token = $user->createToken('device', ['reports:read']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/dashboard/snapshot?period=banana')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure([
                'error' => [
                    'fields' => [
                        'period',
                    ],
                ],
            ]);
    }

    public function test_snapshot_sem_view_reports_mas_com_ability_no_token_retorna_403_policy(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->givePermissionTo('manage_tasks');
        $this->assertFalse($user->fresh()->can('view_reports'));

        $token = $user->createToken('device', ['reports:read']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/dashboard/snapshot')
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'forbidden_policy');
    }

    public function test_snapshot_user_sem_tenant_nao_super_admin_retorna_403_policy(): void
    {
        $user = User::factory()->create(['tenant_id' => null]);
        $user->assignRole('guest');

        $token = $user->createToken('device', ['reports:read']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/dashboard/snapshot')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'forbidden_policy');
    }

    public function test_snapshot_rate_limit_61_chamadas(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $token = $user->createToken('device', ['reports:read']);
        $plain = $token->plainTextToken;

        RateLimiter::clear('api:v1:dashboard:snapshot:user:'.$user->getKey());

        for ($i = 0; $i < 60; $i++) {
            $this->withHeader('Authorization', 'Bearer '.$plain)
                ->getJson('/api/v1/dashboard/snapshot')
                ->assertOk();
        }

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/v1/dashboard/snapshot')
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'rate_limited');
    }
}
