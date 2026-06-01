<?php

namespace Tests\Feature\Console\Backup;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class BackupCleanCommandTest extends TestCase
{
    public function test_remove_apenas_ficheiros_mais_antigos_que_retention(): void
    {
        Storage::fake('local');
        Config::set('backup.disk', 'local');
        Config::set('backup.path', 'backups');
        Config::set('backup.retention_days', 14);

        $disk = Storage::disk('local');
        $disk->makeDirectory('backups');
        $old = 'backups/bgp-testing-2000-01-01-000001.sql.gz';
        $new = 'backups/bgp-testing-2099-01-01-000001.sql.gz';
        $disk->put($old, gzencode('old'));
        $disk->put($new, gzencode('new'));
        touch($disk->path($old), strtotime('-20 days'));
        touch($disk->path($new), strtotime('-1 day'));

        $this->artisan('backup:clean')->assertSuccessful();

        $this->assertFalse($disk->exists($old));
        $this->assertTrue($disk->exists($new));
    }

    public function test_preserva_ficheiros_que_nao_seguem_prefix(): void
    {
        Storage::fake('local');
        Config::set('backup.disk', 'local');
        Config::set('backup.path', 'backups');
        Config::set('backup.retention_days', 1);

        $disk = Storage::disk('local');
        $disk->makeDirectory('backups');
        $other = 'backups/other-old.sql.gz';
        $disk->put($other, 'x');
        touch($disk->path($other), strtotime('-10 days'));

        $this->artisan('backup:clean')->assertSuccessful();

        $this->assertTrue($disk->exists($other));
    }
}
