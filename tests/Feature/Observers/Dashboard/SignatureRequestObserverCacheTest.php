<?php

namespace Tests\Feature\Observers\Dashboard;

use App\Actions\Signatures\PersistSignatureRequestAction;
use App\Enums\DashboardMetricsPeriod;
use App\Enums\SignatureProvider;
use App\Enums\SignatureRequestStatus;
use App\Models\Document;
use App\Models\SignatureRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheKeys;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SignatureRequestObserverCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
    }

    #[Test]
    public function test_created_signature_request_invalida_chave_l1_sentinela(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);
        Cache::put($key, 'X', 600);

        $doc = Document::factory()->create(['tenant_id' => $tenant->id]);
        app(PersistSignatureRequestAction::class)->create($user, [
            'signable_type' => Document::class,
            'signable_id' => $doc->id,
            'provider' => SignatureProvider::Internal->value,
            'title' => 'Assinar',
        ]);

        $this->assertNull(Cache::get($key));
    }

    #[Test]
    public function test_bulk_update_title_sem_observer_preserva_cache_repopulada(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $doc = Document::factory()->create(['tenant_id' => $tenant->id]);
        $req = app(PersistSignatureRequestAction::class)->create($user, [
            'signable_type' => Document::class,
            'signable_id' => $doc->id,
            'provider' => SignatureProvider::Internal->value,
            'title' => 'T1',
        ]);

        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);
        Cache::put($key, 'REPOP', 600);

        SignatureRequest::query()->whereKey($req->id)->update(['title' => 'T2']);

        $this->assertSame('REPOP', Cache::get($key));
    }

    #[Test]
    public function test_update_status_kpi_invalida_cache(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');

        $doc = Document::factory()->create(['tenant_id' => $tenant->id]);
        $req = app(PersistSignatureRequestAction::class)->create($user, [
            'signable_type' => Document::class,
            'signable_id' => $doc->id,
            'provider' => SignatureProvider::Internal->value,
            'title' => 'Assinar',
        ]);

        $key = ExecutiveDashboardCacheKeys::l1Key('t_'.$tenant->id, DashboardMetricsPeriod::ThisMonth);
        Cache::put($key, 'WARM', 600);

        $req->update(['status' => SignatureRequestStatus::Sent]);

        $this->assertNull(Cache::get($key));
    }
}
