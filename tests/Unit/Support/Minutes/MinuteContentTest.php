<?php

namespace Tests\Unit\Support\Minutes;

use App\Support\Minutes\MinuteContent;
use PHPUnit\Framework\TestCase;

final class MinuteContentTest extends TestCase
{
    public function test_conteudo_html_vazio_e_considerado_em_branco(): void
    {
        $this->assertTrue(MinuteContent::isBlank('<p></p>'));
        $this->assertTrue(MinuteContent::isBlank('<p><br></p>'));
        $this->assertFalse(MinuteContent::isBlank('<p>Texto da ata</p>'));
    }
}
