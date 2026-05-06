<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Models\AuditLog;
use App\Models\Board;
use App\Models\Document;
use App\Models\DocumentAccessLog;
use App\Models\DocumentVersion;
use App\Models\Integration;
use App\Models\Meeting;
use App\Models\MeetingAgendaItem;
use App\Models\MeetingParticipant;
use App\Models\Minute;
use App\Models\NotificationCenter;
use App\Models\SignatureRequest;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vote;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_estao_presentes_em_resposta_web(): void
    {
        $middleware = new SecurityHeadersMiddleware;
        $request = Request::create('/up', 'GET');

        $response = $middleware->handle($request, fn (): Response => new Response('ok', 200));

        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertNotEmpty($response->headers->get('Permissions-Policy'));
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_security_headers_estao_presentes_no_painel_quando_autenticado(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        // Garantir que a rota existe para evitar falso positivo em instalações sem Filament carregado.
        $this->assertTrue(Route::has('filament.admin.pages.dashboard') || Route::has('filament.admin.auth.login'));

        $response = $this->actingAs($user)->get('/admin');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotEmpty($response->headers->get('Permissions-Policy'));
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_session_cookie_defaults_sao_configuraveis_por_env(): void
    {
        // Em testes, o .env.testing usa SESSION_DRIVER=array por performance e isolamento.
        // Este teste garante que a config respeita o valor do ambiente.
        $this->assertSame((string) env('SESSION_DRIVER'), config('session.driver'));
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
        $this->assertFalse(config('session.encrypt'));
        $this->assertSame('json', config('session.serialization'));
    }

    public function test_audit_logger_sanitiza_chaves_sensiveis_adicionais(): void
    {
        $tenant = Tenant::factory()->create();
        $actor = User::factory()->create(['tenant_id' => $tenant->id]);

        $log = app(AuditLoggerService::class)->log(
            action: 'custom_test',
            auditable: null,
            oldValues: [],
            newValues: [
                'password' => 'x',
                'token' => 'x',
                'otp' => '123456',
                'recovery_code' => 'x',
                'two_factor_secret' => 'x',
                'client_secret' => 'x',
                'private_key' => 'x',
                'authorization' => 'Bearer x',
                'safe_key' => 'ok',
            ],
            actor: $actor,
            tenantId: $tenant->id,
        );

        $fresh = AuditLog::query()->findOrFail($log->id);
        $values = $fresh->new_values ?? [];

        $this->assertArrayNotHasKey('password', $values);
        $this->assertArrayNotHasKey('token', $values);
        $this->assertArrayNotHasKey('otp', $values);
        $this->assertArrayNotHasKey('recovery_code', $values);
        $this->assertArrayNotHasKey('two_factor_secret', $values);
        $this->assertArrayNotHasKey('client_secret', $values);
        $this->assertArrayNotHasKey('private_key', $values);
        $this->assertArrayNotHasKey('authorization', $values);
        $this->assertSame('ok', $values['safe_key'] ?? null);
    }

    public function test_models_criticos_tem_belongs_to_tenant_ou_excecao_documentada(): void
    {
        $mustHaveTrait = [
            Board::class,
            \App\Models\BoardMember::class,
            Meeting::class,
            MeetingParticipant::class,
            MeetingAgendaItem::class,
            Document::class,
            DocumentVersion::class,
            DocumentAccessLog::class,
            Minute::class,
            Vote::class,
            Task::class,
            Integration::class,
            SignatureRequest::class,
            NotificationCenter::class,
        ];

        $exceptions = [
            Tenant::class,
            User::class,
            AuditLog::class,
        ];

        foreach ($exceptions as $fqcn) {
            $this->assertFalse(in_array(\App\Models\Concerns\BelongsToTenant::class, class_uses_recursive($fqcn), true));
        }

        foreach ($mustHaveTrait as $fqcn) {
            $this->assertTrue(
                in_array(\App\Models\Concerns\BelongsToTenant::class, class_uses_recursive($fqcn), true),
                sprintf('Model %s deve usar BelongsToTenant (ou ser exceção documentada).', $fqcn),
            );
        }
    }
}

