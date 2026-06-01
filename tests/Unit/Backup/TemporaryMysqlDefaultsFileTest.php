<?php

namespace Tests\Unit\Backup;

use App\Services\Backup\TemporaryMysqlDefaultsFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TemporaryMysqlDefaultsFileTest extends TestCase
{
    #[Test]
    public function create_e_delete_remove_ficheiro_do_disco(): void
    {
        $f = new TemporaryMysqlDefaultsFile([
            'username' => 'u',
            'password' => 'p',
            'host' => '127.0.0.1',
            'port' => '3306',
            'unix_socket' => '',
        ]);

        $f->create();
        $path = $f->path();
        $this->assertFileExists($path);
        $this->assertSame(0600, fileperms($path) & 0777);

        $f->delete();
        $this->assertFileDoesNotExist($path);
    }
}
