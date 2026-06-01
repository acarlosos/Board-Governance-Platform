<?php

namespace Tests\Feature\Console\Backup;

use App\Services\Backup\MysqldumpPipelineRunner;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

final class BackupRunCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Config::set('backup.disk', 'local');
        Config::set('backup.path', 'backups');
    }

    public function test_command_cria_ficheiro_gz_com_naming_canonico(): void
    {
        $expectedDb = (string) config('database.connections.mysql.database');

        $this->mock(MysqldumpPipelineRunner::class, function ($mock) use ($expectedDb): void {
            $mock->shouldReceive('run')
                ->once()
                ->withArgs(function (string $defaults, string $db, string $out) use ($expectedDb): bool {
                    $this->assertSame($expectedDb, $db);
                    $this->assertStringEndsWith('.cnf', $defaults);
                    file_put_contents($out, gzencode('-- dump'));

                    return true;
                });
        });

        $this->artisan('backup:run')->assertSuccessful();

        $names = collect(Storage::disk('local')->files('backups'))
            ->map(fn (string $p): string => basename($p));

        $this->assertTrue(
            $names->contains(fn (string $n): bool => (bool) preg_match('/^bgp-'.preg_quote((string) config('app.env'), '/').'-\d{4}-\d{2}-\d{2}-\d{6}\.sql\.gz$/', $n))
        );
    }

    public function test_command_remove_defaults_file_mesmo_em_falha(): void
    {
        $before = count(glob(storage_path('framework/tmp/bgp-backup-*.cnf')) ?: []);

        $this->mock(MysqldumpPipelineRunner::class, function ($mock): void {
            $mock->shouldReceive('run')->once()->andThrow(new RuntimeException('pipeline boom'));
        });

        $this->artisan('backup:run')->assertFailed();

        $after = count(glob(storage_path('framework/tmp/bgp-backup-*.cnf')) ?: []);
        $this->assertSame($before, $after);
    }

    public function test_command_loga_resumo_sem_password(): void
    {
        Event::fake([MessageLogged::class]);

        $this->mock(MysqldumpPipelineRunner::class, function ($mock): void {
            $mock->shouldReceive('run')
                ->once()
                ->andReturnUsing(function (string $defaults, string $db, string $out): void {
                    file_put_contents($out, gzencode('--'));
                });
        });

        $this->artisan('backup:run')->assertSuccessful();

        Event::assertDispatched(MessageLogged::class, function (MessageLogged $e): bool {
            return $e->level === 'info'
                && str_contains((string) $e->message, 'Backup written:')
                && ! str_contains((string) $e->message, 'segredo_nunca_no_log');
        });
    }

    public function test_command_skip_quando_connection_mysql_nao_e_mysql(): void
    {
        Event::fake([MessageLogged::class]);
        Config::set('database.connections.mysql.driver', 'pgsql');

        $this->mock(MysqldumpPipelineRunner::class, function ($mock): void {
            $mock->shouldNotReceive('run');
        });

        $this->artisan('backup:run')->assertSuccessful();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e): bool => $e->level === 'info' && str_contains((string) $e->message, 'skipped'));
    }
}
