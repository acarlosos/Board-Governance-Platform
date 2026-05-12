<?php

namespace App\Console\Commands\Backup;

use App\Services\Backup\MysqldumpPipelineRunner;
use App\Services\Backup\TemporaryMysqlDefaultsFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class BackupRunCommand extends Command
{
    protected $signature = 'backup:run {--only-db : reservado para evolução}';

    protected $description = 'Gera dump MySQL comprimido (gzip) em storage/app/backups/ (fase 18.5).';

    public function handle(MysqldumpPipelineRunner $runner): int
    {
        /** @var array<string, mixed> $mysql */
        $mysql = config('database.connections.mysql');

        if (($mysql['driver'] ?? '') !== 'mysql') {
            Log::info('backup:run skipped: connections.mysql não está definida como driver mysql');

            return self::SUCCESS;
        }

        $database = (string) ($mysql['database'] ?? '');
        if ($database === '') {
            Log::warning('backup:run aborted: nome da base vazio.');

            return self::FAILURE;
        }

        $defaults = new TemporaryMysqlDefaultsFile($mysql);

        try {
            $defaults->create();
        } catch (Throwable $e) {
            Log::warning('backup:run failed preparing defaults file: '.$e->getMessage());

            return self::FAILURE;
        }

        $disk = (string) config('backup.disk');
        $sub = trim((string) config('backup.path'), '/');
        $stamp = now()->format('Y-m-d-His');
        $env = (string) config('app.env');
        $filename = 'bgp-'.$env.'-'.$stamp.'.sql.gz';
        $relative = $sub.'/'.$filename;

        $exit = self::SUCCESS;

        try {
            Storage::disk($disk)->makeDirectory($sub, 0750, true);
            $dirAbs = Storage::disk($disk)->path($sub);
            if (is_dir($dirAbs)) {
                @chmod($dirAbs, 0750);
            }

            $absolute = Storage::disk($disk)->path($relative);
            $runner->run($defaults->path(), $database, $absolute);

            $size = @filesize($absolute);
            $human = $size !== false ? (string) round($size / 1024 / 1024, 1).' MB' : 'tamanho desconhecido';
            Log::info('Backup written: storage/app/'.$sub.'/'.$filename.' ('.$human.')');
        } catch (Throwable $e) {
            Log::warning('backup:run failed: '.$e->getMessage());
            $exit = self::FAILURE;
        } finally {
            $defaults->delete();
        }

        return $exit;
    }
}
