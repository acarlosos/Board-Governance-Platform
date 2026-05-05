<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PHPUnit\Framework\Assert;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $this->assertApplicationUsesIsolatedTestDatabase($app);

        return $app;
    }

    /**
     * Garante que nunca corremos testes contra MySQL ou outra base de desenvolvimento.
     */
    protected function assertApplicationUsesIsolatedTestDatabase(Application $app): void
    {
        Assert::assertTrue(
            $app->runningUnitTests(),
            'A aplicação tem de estar em modo de testes PHPUnit.'
        );

        Assert::assertSame(
            'testing',
            $app->environment(),
            'APP_ENV tem de ser testing para carregar .env.testing.'
        );

        Assert::assertSame(
            'sqlite',
            $app['config']->get('database.default'),
            'Os testes têm de usar DB_CONNECTION=sqlite em .env.testing.'
        );

        $database = $app['config']->get('database.connections.sqlite.database');

        Assert::assertIsString($database);

        // Permitimos SQLite em memória para estabilidade/performance dos testes.
        if ($database === ':memory:') {
            return;
        }

        Assert::assertStringEndsWith('testing.sqlite', $database, 'Use apenas database/testing.sqlite ou :memory: para dados de teste.');

        $resolved = str_starts_with($database, DIRECTORY_SEPARATOR)
            ? $database
            : $app->basePath().DIRECTORY_SEPARATOR.$database;

        Assert::assertFileExists($resolved, 'Crie o ficheiro vazio: touch database/testing.sqlite');
    }
}
