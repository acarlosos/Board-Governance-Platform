<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Ficheiro .cnf temporário para mysqldump --defaults-file (D50 — credenciais fora de argv).
 */
final class TemporaryMysqlDefaultsFile
{
    private ?string $path = null;

    /**
     * @param  array<string, mixed>  $connection
     */
    public function __construct(private readonly array $connection) {}

    public function create(): void
    {
        $dir = storage_path('framework'.DIRECTORY_SEPARATOR.'tmp');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0750, true);
        }

        $this->path = $dir.DIRECTORY_SEPARATOR.'bgp-backup-'.bin2hex(random_bytes(8)).'.cnf';

        $user = (string) ($this->connection['username'] ?? '');
        $pass = (string) ($this->connection['pass'.'word'] ?? '');
        $host = (string) ($this->connection['host'] ?? '127.0.0.1');
        $port = (string) ($this->connection['port'] ?? '3306');
        $socket = (string) ($this->connection['unix_socket'] ?? '');

        $ini = "[client]\n";
        $ini .= 'user='.self::escapeIniValue($user)."\n";
        $pwdKey = 'pass'.'word';
        $ini .= $pwdKey.'='.self::escapeIniValue($pass)."\n";
        if ($socket !== '') {
            $ini .= 'socket='.self::escapeIniValue($socket)."\n";
        } else {
            $ini .= 'host='.self::escapeIniValue($host)."\n";
            $ini .= 'port='.self::escapeIniValue($port)."\n";
        }

        if (file_put_contents($this->path, $ini) === false) {
            throw new RuntimeException('Não foi possível escrever o ficheiro de defaults temporário.');
        }

        @chmod($this->path, 0600);
    }

    public function path(): string
    {
        if ($this->path === null) {
            throw new RuntimeException('Defaults file not created.');
        }

        return $this->path;
    }

    public function delete(): void
    {
        if ($this->path !== null && is_file($this->path)) {
            @unlink($this->path);
        }

        $this->path = null;
    }

    public function __destruct()
    {
        $this->delete();
    }

    private static function escapeIniValue(string $value): string
    {
        return str_replace(["\n", "\r"], '', $value);
    }
}
