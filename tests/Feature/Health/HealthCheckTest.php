<?php

namespace Tests\Feature\Health;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_retorna_200_quando_db_e_cache_ok(): void
    {
        $this->get('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('db', 'ok')
            ->assertJsonPath('cache', 'ok')
            ->assertJsonStructure(['status', 'db', 'cache', 'app_env']);
    }
}
