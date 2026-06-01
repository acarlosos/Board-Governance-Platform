<?php

namespace App\Console\Commands\Backup;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class BackupCleanCommand extends Command
{
    protected $signature = 'backup:clean';

    protected $description = 'Remove dumps bgp-*.sql.gz mais antigos que a retenção configurada.';

    public function handle(): int
    {
        $disk = (string) config('backup.disk');
        $sub = trim((string) config('backup.path'), '/');
        $base = Storage::disk($disk)->path($sub);

        if (! is_dir($base)) {
            Log::info('backup:clean — directório inexistente, nada a fazer');

            return self::SUCCESS;
        }

        $threshold = now()->subDays((int) config('backup.retention_days'))->getTimestamp();
        $deleted = 0;
        $kept = 0;

        foreach (glob($base.'/bgp-*.sql.gz') ?: [] as $file) {
            if (! is_file($file)) {
                continue;
            }

            $name = basename($file);
            if (! str_starts_with($name, 'bgp-')) {
                $kept++;

                continue;
            }

            if (filemtime($file) < $threshold) {
                @unlink($file);
                $deleted++;
            } else {
                $kept++;
            }
        }

        Log::info("backup:clean — removed {$deleted}, kept {$kept}");

        return self::SUCCESS;
    }
}
