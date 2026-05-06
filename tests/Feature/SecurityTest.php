<?php

namespace Tests\Feature;

use App\Actions\Filament\PersistPanelUserAction;
use App\Actions\Security\RevokeAuthSessionAction;
use App\Actions\Security\UpdateOwnPasswordAction;
use App\Enums\AuditAction;
use App\Enums\AuthSessionStatus;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\AuthSession;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Security\AuthSessionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_login_event_cria_auth_session_e_audit_log(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        Event::dispatch(new Login('web', $user, false));

        $this->assertSame(1, AuthSession::query()->where('user_id', $user->id)->count());
        $session = AuthSession::query()->where('user_id', $user->id)->first();
        $this->assertSame(AuthSessionStatus::Active, $session->status);
        $this->assertSame((int) $tenant->id, (int) $session->tenant_id);

        $this->assertTrue(
            AuditLog::query()
                ->where('action', AuditAction::Login->value)
                ->where('user_id', $user->id)
                ->exists(),
        );
    }

    public function test_logout_event_fecha_auth_session_ativa(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        Event::dispatch(new Login('web', $user, false));
        Event::dispatch(new Logout('web', $user));

        $session = AuthSession::query()->where('user_id', $user->id)->first();
        $this->assertSame(AuthSessionStatus::Closed, $session->status);
        $this->assertNotNull($session->logout_at);

        $this->assertTrue(
            AuditLog::query()
                ->where('action', AuditAction::Logout->value)
                ->where('user_id', $user->id)
                ->exists(),
        );
    }

    public function test_failed_login_gera_audit_log_sem_credentials(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'foo@bar.test']);

        Event::dispatch(new Failed('web', $user, ['email' => 'foo@bar.test', 'password' => 'wrong']));

        $log = AuditLog::query()->where('action', AuditAction::FailedLogin->value)->first();
        $this->assertNotNull($log);
        $this->assertSame((int) $user->id, (int) $log->user_id);
        $this->assertSame((int) $tenant->id, (int) $log->tenant_id);

        $values = $log->new_values ?? [];
        $this->assertArrayNotHasKey('password', $values);
    }

    public function test_two_factor_enabled_gera_audit_log_sem_expor_secret(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user);
        $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

        $logs = AuditLog::query()
            ->where('action', AuditAction::TwoFactorEnabled->value)
            ->where('user_id', $user->id)
            ->get();

        $this->assertCount(1, $logs);
        $values = $logs->first()->new_values ?? [];
        $this->assertArrayNotHasKey('two_factor_secret', $values);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $values);
    }

    public function test_two_factor_disabled_gera_audit_log(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->actingAs($user);
        $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

        $user->saveAppAuthenticationSecret(null);

        $this->assertTrue(
            AuditLog::query()
                ->where('action', AuditAction::TwoFactorDisabled->value)
                ->where('user_id', $user->id)
                ->exists(),
        );
    }

    public function test_two_factor_secret_e_recovery_codes_sao_persistidos_encriptados(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $secret = 'JBSWY3DPEHPK3PXP';
        $codes = ['code-1', 'code-2'];

        $user->saveAppAuthenticationSecret($secret);
        $user->saveAppAuthenticationRecoveryCodes($codes);

        $raw = \DB::table('users')->where('id', $user->id)->first();

        $this->assertNotNull($raw->two_factor_secret);
        $this->assertNotSame($secret, $raw->two_factor_secret);
        $this->assertNotNull($raw->two_factor_recovery_codes);
        $this->assertNotSame(json_encode($codes), $raw->two_factor_recovery_codes);

        $user->refresh();
        $this->assertSame($secret, $user->two_factor_secret);
        $this->assertSame($codes, $user->two_factor_recovery_codes);
    }

    public function test_revoke_auth_session_marca_como_closed_e_audita(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $other = User::factory()->create(['tenant_id' => $tenant->id]);
        $session = AuthSession::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $other->id,
            'status' => AuthSessionStatus::Active,
        ]);

        app(RevokeAuthSessionAction::class)->execute($admin, $session->fresh());

        $session->refresh();
        $this->assertSame(AuthSessionStatus::Closed, $session->status);
        $this->assertNotNull($session->logout_at);
        $this->assertTrue(
            AuditLog::query()
                ->where('action', AuditAction::SessionRevoked->value)
                ->where('auditable_id', $session->id)
                ->exists(),
        );
    }

    public function test_tenant_admin_nao_revoga_sessao_de_outro_tenant(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();

        $admin = User::factory()->create(['tenant_id' => $tA->id]);
        $admin->assignRole('tenant_admin');

        $session = AuthSession::factory()->create([
            'tenant_id' => $tB->id,
            'user_id' => User::factory()->create(['tenant_id' => $tB->id])->id,
            'status' => AuthSessionStatus::Active,
        ]);

        $this->expectException(AuthorizationException::class);
        app(RevokeAuthSessionAction::class)->execute($admin, $session);
    }

    public function test_usuario_comum_so_revoga_propria_sessao(): void
    {
        $tenant = Tenant::factory()->create();
        $alice = User::factory()->create(['tenant_id' => $tenant->id]);
        $alice->assignRole('board_member');
        $bob = User::factory()->create(['tenant_id' => $tenant->id]);

        $bobSession = AuthSession::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $bob->id,
            'status' => AuthSessionStatus::Active,
        ]);

        $this->expectException(AuthorizationException::class);
        app(RevokeAuthSessionAction::class)->execute($alice, $bobSession);
    }

    public function test_usuario_pode_encerrar_propria_sessao(): void
    {
        $tenant = Tenant::factory()->create();
        $alice = User::factory()->create(['tenant_id' => $tenant->id]);
        $alice->assignRole('board_member');

        $session = AuthSession::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $alice->id,
            'status' => AuthSessionStatus::Active,
        ]);

        app(RevokeAuthSessionAction::class)->execute($alice, $session->fresh());
        $session->refresh();
        $this->assertSame(AuthSessionStatus::Closed, $session->status);
    }

    public function test_super_admin_revoga_sessao_de_qualquer_tenant(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();
        $super = User::factory()->create(['tenant_id' => null]);
        $super->assignRole('super_admin');

        $session = AuthSession::factory()->create([
            'tenant_id' => $tB->id,
            'user_id' => User::factory()->create(['tenant_id' => $tB->id])->id,
            'status' => AuthSessionStatus::Active,
        ]);

        app(RevokeAuthSessionAction::class)->execute($super, $session->fresh());
        $session->refresh();
        $this->assertSame(AuthSessionStatus::Closed, $session->status);
    }

    public function test_persist_panel_user_action_bloqueia_senha_fraca(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $this->expectException(ValidationException::class);

        app(PersistPanelUserAction::class)->create($admin, [
            'name' => 'New User',
            'email' => 'new@user.test',
            'password' => 'simple', // não tem maiúscula, número, símbolo, e tem < 8
            'tenant_id' => $tenant->id,
            'locale' => 'pt_BR',
            'status' => UserStatus::Active->value,
            'is_super_admin' => false,
            'roles' => ['board_member'],
        ]);
    }

    public function test_persist_panel_user_action_aceita_senha_forte(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $created = app(PersistPanelUserAction::class)->create($admin, [
            'name' => 'New User',
            'email' => 'new@user.test',
            'password' => 'StrongPass!1',
            'tenant_id' => $tenant->id,
            'locale' => 'pt_BR',
            'status' => UserStatus::Active->value,
            'is_super_admin' => false,
            'roles' => ['board_member'],
        ]);

        $this->assertNotNull($created->getKey());
    }

    public function test_update_own_password_action_bloqueia_senha_fraca(): void
    {
        RateLimiter::clear('password-update:0');
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'password' => Hash::make('OldStrong!1Pass'),
        ]);

        $this->expectException(ValidationException::class);
        app(UpdateOwnPasswordAction::class)->execute($user, [
            'current_password' => 'OldStrong!1Pass',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);
    }

    public function test_update_own_password_action_atualiza_quando_dados_validos(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'password' => Hash::make('OldStrong!1Pass'),
        ]);

        $this->actingAs($user);
        app(UpdateOwnPasswordAction::class)->execute($user, [
            'current_password' => 'OldStrong!1Pass',
            'password' => 'NewerStrong!2Pass',
            'password_confirmation' => 'NewerStrong!2Pass',
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('NewerStrong!2Pass', $user->password));
        $this->assertTrue(
            AuditLog::query()
                ->where('action', AuditAction::PasswordChanged->value)
                ->where('user_id', $user->id)
                ->exists(),
        );
    }

    public function test_update_own_password_action_recusa_senha_atual_invalida(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'password' => Hash::make('OldStrong!1Pass'),
        ]);

        $this->expectException(ValidationException::class);
        app(UpdateOwnPasswordAction::class)->execute($user, [
            'current_password' => 'WrongPass!1',
            'password' => 'NewerStrong!2Pass',
            'password_confirmation' => 'NewerStrong!2Pass',
        ]);
    }

    public function test_auth_session_service_record_failed_login_sem_email_nao_quebra(): void
    {
        app(AuthSessionService::class)->recordFailedLogin(null);
        $this->assertTrue(
            AuditLog::query()->where('action', AuditAction::FailedLogin->value)->exists(),
        );
    }

    public function test_auth_session_expire_marca_como_expired(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $session = AuthSession::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => AuthSessionStatus::Active,
        ]);

        app(AuthSessionService::class)->expire($session->fresh());
        $session->refresh();
        $this->assertSame(AuthSessionStatus::Expired, $session->status);
        $this->assertTrue(
            AuditLog::query()
                ->where('action', AuditAction::SessionExpired->value)
                ->where('auditable_id', $session->id)
                ->exists(),
        );
    }

    public function test_resolve_visible_session_for_actor_aplica_escopo_antes_de_autorizar(): void
    {
        $tA = Tenant::factory()->create();
        $tB = Tenant::factory()->create();

        $adminA = User::factory()->create(['tenant_id' => $tA->id]);
        $adminA->assignRole('tenant_admin');

        $userA = User::factory()->create(['tenant_id' => $tA->id]);
        $userA->assignRole('board_member');

        $sessionA = AuthSession::factory()->create([
            'tenant_id' => $tA->id,
            'user_id' => $userA->id,
            'status' => AuthSessionStatus::Active,
        ]);

        $sessionB = AuthSession::factory()->create([
            'tenant_id' => $tB->id,
            'user_id' => User::factory()->create(['tenant_id' => $tB->id])->id,
            'status' => AuthSessionStatus::Active,
        ]);

        $resolved = app(AuthSessionService::class)->resolveVisibleSessionForActor($adminA, $sessionA->id);
        $this->assertSame($sessionA->id, $resolved->id);

        $this->expectException(ValidationException::class);
        app(AuthSessionService::class)->resolveVisibleSessionForActor($adminA, $sessionB->id);
    }

    public function test_super_admin_resolve_visible_session_global(): void
    {
        $t = Tenant::factory()->create();
        $super = User::factory()->create(['tenant_id' => null]);
        $super->assignRole('super_admin');

        $session = AuthSession::factory()->create([
            'tenant_id' => $t->id,
            'user_id' => User::factory()->create(['tenant_id' => $t->id])->id,
            'status' => AuthSessionStatus::Active,
        ]);

        $resolved = app(AuthSessionService::class)->resolveVisibleSessionForActor($super, $session->id);
        $this->assertSame($session->id, $resolved->id);
    }

    public function test_revogar_sessao_ja_fechada_e_idempotente(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        config()->set('session.driver', 'database');

        $session = AuthSession::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => User::factory()->create(['tenant_id' => $tenant->id])->id,
            'status' => AuthSessionStatus::Closed,
            'logout_at' => now(),
            'session_id' => 'session-123',
        ]);

        \DB::table('sessions')->insert([
            'id' => 'session-123',
            'user_id' => $session->user_id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => '',
            'last_activity' => time(),
        ]);

        app(RevokeAuthSessionAction::class)->executeById($admin, $session->id);

        $session->refresh();
        $this->assertSame(AuthSessionStatus::Closed, $session->status);
        $this->assertTrue(\DB::table('sessions')->where('id', 'session-123')->doesntExist());
    }

    public function test_touch_activity_nao_reativa_sessao_encerrada(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->actingAs($user);

        $session = AuthSession::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => AuthSessionStatus::Closed,
            'session_id' => 'session-touch',
            'last_activity_at' => now()->subHour(),
        ]);

        app(AuthSessionService::class)->touchActivity('session-touch');
        $session->refresh();
        $this->assertSame(AuthSessionStatus::Closed, $session->status);
        $this->assertTrue($session->last_activity_at->lt(now()->subMinute()));
    }

    public function test_auditoria_de_password_changed_usa_actor_autenticado_quando_existe(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $target = User::factory()->create([
            'tenant_id' => $tenant->id,
            'password' => Hash::make('OldStrong!1Pass'),
        ]);

        $this->actingAs($admin);
        $target->password = 'NewerStrong!2Pass';
        $target->save();

        $this->assertTrue(
            AuditLog::query()
                ->where('action', AuditAction::PasswordChanged->value)
                ->where('user_id', $admin->id)
                ->where('auditable_id', $target->id)
                ->exists(),
        );
    }
}
