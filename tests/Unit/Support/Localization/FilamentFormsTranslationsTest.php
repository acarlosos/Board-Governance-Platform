<?php

namespace Tests\Unit\Support\Localization;

use Tests\TestCase;

class FilamentFormsTranslationsTest extends TestCase
{
    public function test_filament_select_placeholder_is_translated_in_portuguese(): void
    {
        $message = __('filament-forms::components.select.placeholder', [], 'pt_BR');

        $this->assertSame('Selecione uma opção', $message);
        $this->assertNotSame('Select an option', $message);
    }

    public function test_filament_select_no_options_message_is_translated_in_portuguese(): void
    {
        $message = __('filament-forms::components.select.no_options_message', [], 'pt_BR');

        $this->assertSame('Nenhuma opção disponível.', $message);
        $this->assertNotSame('No options available.', $message);
    }

    public function test_filament_select_placeholder_is_translated_in_spanish(): void
    {
        $message = __('filament-forms::components.select.placeholder', [], 'es');

        $this->assertSame('Seleccione una opción', $message);
        $this->assertNotSame('Select an option', $message);
    }

    public function test_filament_select_no_options_message_is_translated_in_spanish(): void
    {
        $message = __('filament-forms::components.select.no_options_message', [], 'es');

        $this->assertSame('No hay opciones disponibles.', $message);
        $this->assertNotSame('No options available.', $message);
    }
}
