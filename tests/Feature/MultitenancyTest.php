<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Models\TestingTenantScopedItem;
use Tests\TestCase;

class MultitenancyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('testing_tenant_scoped_items');
        Schema::create('testing_tenant_scoped_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('testing_tenant_scoped_items');

        parent::tearDown();
    }

    public function test_tenant_pode_ser_criado(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Acme', 'slug' => 'acme']);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'slug' => 'acme',
        ]);
    }

    public function test_utilizador_pertence_ao_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertTrue($user->tenant->is($tenant));
    }

    public function test_tenant_id_preenchido_automaticamente_em_model_com_belongs_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user);

        $item = TestingTenantScopedItem::query()->create(['label' => 'Registo automático']);

        $this->assertSame($tenant->id, $item->tenant_id);
    }

    public function test_dados_de_um_tenant_nao_aparecem_para_utilizador_de_outro_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

        $itemA = TestingTenantScopedItem::factory()->create(['tenant_id' => $tenantA->id]);
        $itemB = TestingTenantScopedItem::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($userA);
        $this->assertCount(1, TestingTenantScopedItem::all());
        $this->assertTrue(TestingTenantScopedItem::first()->is($itemA));

        $this->actingAs($userB);
        $this->assertCount(1, TestingTenantScopedItem::all());
        $this->assertTrue(TestingTenantScopedItem::first()->is($itemB));
    }

    public function test_consultas_sem_autenticacao_nao_falham_e_nao_aplicam_scope(): void
    {
        TestingTenantScopedItem::factory()->count(2)->create();

        $this->assertSame(2, TestingTenantScopedItem::query()->count());
    }

    public function test_super_admin_bootstrap_ve_todos_os_registos_scoped(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $admin = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'is_super_admin' => true,
        ]);

        TestingTenantScopedItem::factory()->create(['tenant_id' => $tenantA->id]);
        TestingTenantScopedItem::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($admin);

        $this->assertSame(2, TestingTenantScopedItem::query()->count());
    }

    public function test_tenant_resolver_seguro_sem_sessao(): void
    {
        $resolver = app(TenantResolver::class);

        $this->assertNull($resolver->currentId());
        $this->assertNull($resolver->current());
    }

    public function test_tenant_resolver_com_sessao(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user);

        $resolver = app(TenantResolver::class);

        $this->assertSame($tenant->id, $resolver->currentId());
        $this->assertTrue($resolver->current()->is($tenant));
    }
}
