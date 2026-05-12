<?php

namespace Tests\Feature\Observers\Dashboard;

use App\Actions\Signatures\PersistSignatureRequestAction;
use App\Actions\Signatures\PersistSignatureSignerAction;
use App\Enums\DashboardMetricsPeriod;
use App\Enums\SignatureProvider;
use App\Models\Document;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheKeys;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SignatureRequestSignerNoInvalidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
    }

    #[Test]
    public function test_mutacao_signer_nome_nao_invalida_dashboard_cache(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $signerUser = User::factory()->create(['tenant_id' => $tenant->id]);

        $doc = Document::factory()->create(['tenant_id' => $tenant->id]);
        $req = app(PersistSignatureRequestAction::class)->create($admin, [
            'signable_type' => Document::class,
            'signable_id' => $doc->id,
            'provider' => SignatureProvider::Internal->value,
            'title' => 'Assinar',
        ]);

        $signer = app(PersistSignatureSignerAction::class)->create($admin, $req, [
            'user_id' => $signerUser->id,
            'name' => 'Nome A',
            'email' => $signerUser->email,
        ]);

        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);
        Cache::put($key, 'SENTINEL', 600);

        app(PersistSignatureSignerAction::class)->update($admin, $signer, [
            'user_id' => $signerUser->id,
            'name' => 'Nome B',
            'email' => $signerUser->email,
        ]);

        $this->assertSame('SENTINEL', Cache::get($key));
    }
}
