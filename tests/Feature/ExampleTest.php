<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A raiz do produto redireciona para o login do painel Filament (sem página "welcome").
     */
    public function test_home_redirects_to_filament_admin_login(): void
    {
        $this->get('/')
            ->assertRedirect('/admin/login');
    }
}
