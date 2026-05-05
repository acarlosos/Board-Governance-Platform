<?php

namespace Tests\Feature;

use App\Actions\Documents\PersistDocumentAction;
use App\Actions\Documents\RecordDocumentAccessAction;
use App\Actions\Documents\UploadDocumentVersionAction;
use App\Enums\DocumentAccessAction;
use App\Models\AuditLog;
use App\Models\Board;
use App\Models\Document;
use App\Models\DocumentAccessLog;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function makeUploadedFile(string $name, string $mimeType, int $kilobytes = 10): UploadedFile
    {
        $dir = storage_path('framework/testing');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = @tempnam($dir, 'upload_');
        if (! is_string($path)) {
            $this->fail('Falha ao criar arquivo temporário para teste.');
        }

        file_put_contents($path, random_bytes($kilobytes * 1024));

        return new UploadedFile(
            path: $path,
            originalName: $name,
            mimeType: $mimeType,
            error: null,
            test: true,
        );
    }

    public function test_tenant_admin_nao_pode_criar_documento_vinculado_a_board_de_outro_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $tenantAAdmin = User::factory()->create(['tenant_id' => $tenantA->id]);
        $tenantAAdmin->assignRole('tenant_admin');

        $boardTenantB = Board::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($tenantAAdmin);

        $this->expectException(ValidationException::class);
        app(PersistDocumentAction::class)->create($tenantAAdmin, [
            'tenant_id' => $tenantA->id,
            'board_id' => $boardTenantB->id,
            'meeting_id' => null,
            'title' => 'Doc',
            'description' => null,
            'category' => null,
            'status' => 'draft',
        ]);
    }

    public function test_tenant_admin_nao_pode_criar_documento_vinculado_a_meeting_de_outro_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $tenantAAdmin = User::factory()->create(['tenant_id' => $tenantA->id]);
        $tenantAAdmin->assignRole('tenant_admin');

        $meetingTenantB = Meeting::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($tenantAAdmin);

        $this->expectException(ValidationException::class);
        app(PersistDocumentAction::class)->create($tenantAAdmin, [
            'tenant_id' => $tenantA->id,
            'board_id' => null,
            'meeting_id' => $meetingTenantB->id,
            'title' => 'Doc',
            'description' => null,
            'category' => null,
            'status' => 'draft',
        ]);
    }

    public function test_upload_cria_versao_incremental_em_storage_privado_por_tenant_e_atualiza_current_version_id(): void
    {
        Storage::fake('local');
        Config::set('board.documents.disk', 'local');

        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $this->actingAs($admin);

        $document = Document::factory()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Doc',
        ]);

        $file1 = $this->makeUploadedFile('original.pdf', 'application/pdf', 10);
        $v1 = app(UploadDocumentVersionAction::class)->upload($admin, $document, $file1);

        $document->refresh();
        $this->assertNotNull($document->current_version_id);
        $this->assertSame($v1->id, $document->current_version_id);
        $this->assertSame(1, $v1->version_number);
        $this->assertStringContainsString('private/tenants/'.$tenant->id.'/documents/'.$document->id.'/versions/1/', $v1->file_path);
        Storage::disk('local')->assertExists($v1->file_path);
        $this->assertNotSame('original.pdf', basename($v1->file_path));

        $file2 = $this->makeUploadedFile('another.pdf', 'application/pdf', 10);
        $v2 = app(UploadDocumentVersionAction::class)->upload($admin, $document, $file2);

        $document->refresh();
        $this->assertSame(2, $v2->version_number);
        $this->assertSame($v2->id, $document->current_version_id);
        $this->assertStringContainsString('/versions/2/', $v2->file_path);
    }

    public function test_upload_valida_extensao_no_backend(): void
    {
        Storage::fake('local');
        Config::set('board.documents.disk', 'local');

        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $this->actingAs($admin);

        $document = Document::factory()->create(['tenant_id' => $tenant->id]);
        $file = $this->makeUploadedFile('malware.exe', 'application/octet-stream', 10);

        $this->expectException(ValidationException::class);
        app(UploadDocumentVersionAction::class)->upload($admin, $document, $file);
    }

    public function test_upload_bloqueia_cross_tenant_document(): void
    {
        Storage::fake('local');
        Config::set('board.documents.disk', 'local');

        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $admin = User::factory()->create(['tenant_id' => $tenantA->id]);
        $admin->assignRole('tenant_admin');
        $this->actingAs($admin);

        $docTenantB = Document::factory()->create(['tenant_id' => $tenantB->id]);
        $file = $this->makeUploadedFile('a.pdf', 'application/pdf', 10);

        $this->expectException(ValidationException::class);
        app(UploadDocumentVersionAction::class)->upload($admin, $docTenantB, $file);
    }

    public function test_registra_access_log_visualizacao_e_download(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $this->actingAs($admin);

        $document = Document::factory()->create(['tenant_id' => $tenant->id]);

        app(RecordDocumentAccessAction::class)->record($admin, $document, DocumentAccessAction::Viewed);
        app(RecordDocumentAccessAction::class)->record($admin, $document, DocumentAccessAction::Downloaded);

        $this->assertSame(2, DocumentAccessLog::query()->where('document_id', $document->id)->count());
        $this->assertSame(
            ['viewed', 'downloaded'],
            DocumentAccessLog::query()->where('document_id', $document->id)->orderBy('id')->pluck('action')->all(),
        );
    }

    public function test_auditoria_de_document_version_nao_expoe_file_path(): void
    {
        Storage::fake('local');
        Config::set('board.documents.disk', 'local');

        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');
        $this->actingAs($admin);

        $document = Document::factory()->create(['tenant_id' => $tenant->id]);
        $file1 = $this->makeUploadedFile('original.pdf', 'application/pdf', 10);

        $version = app(UploadDocumentVersionAction::class)->upload($admin, $document, $file1);

        $lastLog = AuditLog::query()->latest('id')->first();
        $this->assertNotNull($lastLog);

        $newValues = $lastLog->new_values ?? [];
        $this->assertIsArray($newValues);

        $flat = json_encode($newValues);
        $this->assertIsString($flat);
        $this->assertStringNotContainsString('file_path', $flat);
        $this->assertStringNotContainsString($version->file_path, $flat);
    }
}

