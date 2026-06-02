<?php

namespace Tests\Feature\Filament\Auth;

use Tests\TestCase;

class PgTrustLoginLabelsTest extends TestCase
{
    public function test_login_exibe_labels_do_formulario_em_portugues(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertSee(__('login.pgtrust.form.email'), false);
        $response->assertSee(__('login.pgtrust.form.password'), false);
        $response->assertSee(__('login.pgtrust.form.remember'), false);
    }

    public function test_login_css_forca_cor_dos_labels_no_card_claro(): void
    {
        $css = file_get_contents(public_path('css/app/bgp-login.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('.bgp-login .bgp-login__form-body .fi-fo-field-label-content', $css);
        $this->assertStringContainsString('color: #111827', $css);
    }
}
