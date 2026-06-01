<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Executa mysqldump | gzip para um ficheiro de saída (D45, D50).
 */
class MysqldumpPipelineRunner
{
    public function run(string $defaultsFilePath, string $database, string $outputAbsolutePath): void
    {
        $defaultsFilePath = realpath($defaultsFilePath) ?: $defaultsFilePath;

        $dir = dirname($outputAbsolutePath);
        if (! is_dir($dir) && ! mkdir($dir, 0750, true) && ! is_dir($dir)) {
            throw new RuntimeException('Não foi possível criar o directório de backup.');
        }

        // Um único shell: pipe + redireccionamento; credenciais só no --defaults-file.
        $inner = sprintf(
            '%s --defaults-file=%s --single-transaction --quick --routines --triggers --no-tablespaces %s | gzip -c > %s',
            escapeshellcmd('mysqldump'),
            escapeshellarg($defaultsFilePath),
            escapeshellarg($database),
            escapeshellarg($outputAbsolutePath)
        );

        $result = Process::timeout(3600)->run(['bash', '-ec', $inner]);

        if (! $result->successful()) {
            throw new RuntimeException(trim($result->errorOutput() ?: $result->output()) ?: 'mysqldump pipeline failed.');
        }
    }
}
