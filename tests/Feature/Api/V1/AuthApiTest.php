<?php

namespace Tests\Feature\Api\V1;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

final class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_login_com_credenciais_validas_retorna_envelope_e_cria_audit_sem_token(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'user@tenant.test',
            'password' => Hash::make('StrongPass!1'),
        ]);

        $res = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@tenant.test',
            'password' => 'StrongPass!1',
            'device_name' => 'iPhone',
        ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.api_version', 'v1')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'token_type',
                    'expires_at',
                    'user' => ['id', 'name', 'email', 'tenant_id', 'is_super_admin'],
                    'tenant' => ['id', 'name', 'slug'],
                    'abilities',
                ],
                'meta' => ['request_id', 'api_version'],
            ]);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::ApiLogin->value)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::TokenCreated->value)->exists());

        $log = AuditLog::query()->where('action', AuditAction::TokenCreated->value)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertIsArray($log->new_values);
        $this->assertArrayNotHasKey('token', $log->new_values);
        $this->assertArrayNotHasKey('authorization', $log->new_values);
    }

    public function test_login_invalido_nao_vaza_detalhes(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'user@tenant.test',
            'password' => Hash::make('StrongPass!1'),
        ]);

        $res = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@tenant.test',
            'password' => 'WrongPass!1',
            'device_name' => 'iPhone',
        ]);

        $res->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_rate_limit_de_login_funciona(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'user@tenant.test',
            'password' => Hash::make('StrongPass!1'),
        ]);

        for ($i = 0; $i < 6; $i++) {
            $res = $this->postJson('/api/v1/auth/login', [
                'email' => 'user@tenant.test',
                'password' => 'WrongPass!1',
                'device_name' => 'iPhone',
            ]);
        }

        $res->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'rate_limited')
            ->assertJsonPath('meta.api_version', 'v1');
    }

    public function test_me_exige_auth_read(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $token = $user->createToken('device', ['tokens:read:self']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_token_sem_auth_read_nao_acessa_me(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $token = $user->createToken('device', ['tokens:read:self']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(403);
    }

    public function test_listagem_de_tokens_exige_tokens_read_self(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $token = $user->createToken('device', ['auth:read']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/auth/tokens')
            ->assertStatus(403);
    }

    public function test_criar_token_exige_tokens_manage_self(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $token = $user->createToken('device', ['tokens:read:self', 'auth:read']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/auth/tokens', [
            'device_name' => 'Zapier',
            'abilities' => ['auth:read'],
        ])->assertStatus(403);
    }

    public function test_logout_revoga_token_atual(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $token = $user->createToken('device', ['auth:read']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.revoked', true);

        $this->assertFalse(PersonalAccessToken::query()->whereKey($token->accessToken->getKey())->exists());
    }

    public function test_revogar_token_proprio_funciona_e_nao_revoga_de_outro_user(): void
    {
        $t = Tenant::factory()->create();
        $u1 = User::factory()->create(['tenant_id' => $t->id]);
        $u2 = User::factory()->create(['tenant_id' => $t->id]);

        $own = $u1->createToken('own', ['auth:read'])->accessToken;
        $other = $u2->createToken('other', ['auth:read'])->accessToken;

        $caller = $u1->createToken('caller', ['tokens:manage:self']);

        $this->withHeader('Authorization', 'Bearer '.$caller->plainTextToken)
            ->deleteJson('/api/v1/auth/tokens/'.$own->getKey())
            ->assertOk()
            ->assertJsonPath('data.revoked', true);

        $this->assertFalse(PersonalAccessToken::query()->whereKey($own->getKey())->exists());

        $this->withHeader('Authorization', 'Bearer '.$caller->plainTextToken)
            ->deleteJson('/api/v1/auth/tokens/'.$other->getKey())
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'not_found');
    }

    public function test_login_intersecta_abilities_solicitadas(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'user@tenant.test',
            'password' => Hash::make('StrongPass!1'),
        ]);

        $res = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@tenant.test',
            'password' => 'StrongPass!1',
            'device_name' => 'iPhone',
            'abilities' => ['auth:read', 'tokens:manage:self', 'invalid:ability'],
        ]);

        $res->assertOk()
            ->assertJsonPath('success', true);

        $abilities = $res->json('data.abilities');
        $this->assertContains('auth:read', $abilities);
        $this->assertContains('tokens:manage:self', $abilities);
        $this->assertContains('tokens:read:self', $abilities);
        $this->assertNotContains('invalid:ability', $abilities);
    }
}

