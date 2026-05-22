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
     * Garante isolamento: SQLite por defeito (CI/local) ou MySQL só no perfil
     * documentado em docs/testing.md (smoke 17.7 — `.env.testing.mysql`).
     */
    protected function assertApplicationUsesIsolatedTestDatabase(Application $app): void
    {
        // Laravel só considera runningUnitTests() quando env === 'testing'; o smoke MySQL usa testing.mysql.
        Assert::assertTrue(
            $app->runningUnitTests() || $app->environment('testing.mysql'),
            'APP_ENV tem de ser testing (phpunit.xml) ou testing.mysql (phpunit.mysql.xml).'
        );

        if ($app->environment('testing.mysql')) {
            Assert::assertFileExists(
                $app->basePath('.env.testing.mysql'),
                'Smoke MySQL: copie .env.testing.mysql.example para .env.testing.mysql e configure DB_*.'
            );

            Assert::assertTrue(
                $app['config']->get('app.testing_mysql_smoke') === true,
                'Smoke MySQL: defina TESTING_MYSQL_SMOKE=true em .env.testing.mysql (ver .env.testing.mysql.example).'
            );

            Assert::assertSame(
                'mysql',
                $app['config']->get('database.default'),
                'Smoke MySQL: DB_CONNECTION tem de ser mysql em .env.testing.mysql.'
            );

            $dbName = (string) $app['config']->get('database.connections.mysql.database');

            Assert::assertNotSame('', $dbName, 'Smoke MySQL: DB_DATABASE não pode ser vazio.');
            Assert::assertMatchesRegularExpression(
                '/_(test|testing|smoke_test)$/i',
                $dbName,
                'Smoke MySQL: use um nome dedicado (ex.: *_test, *_testing, *_smoke_test); nunca produção.'
            );

            return;
        }

        Assert::assertSame(
            'testing',
            $app->environment(),
            'APP_ENV tem de ser testing (phpunit.xml) ou testing.mysql (phpunit.mysql.xml).'
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
