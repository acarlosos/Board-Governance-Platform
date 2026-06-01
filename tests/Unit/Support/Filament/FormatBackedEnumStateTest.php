<?php

namespace Tests\Unit\Support\Filament;

use App\Enums\VoteStatus;
use App\Enums\VoteType;
use App\Support\Filament\FormatBackedEnumState;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FormatBackedEnumStateTest extends TestCase
{
    #[Test]
    public function test_extrai_value_de_backed_enum(): void
    {
        $this->assertSame('open', FormatBackedEnumState::value(VoteType::Open));
        $this->assertSame('draft', FormatBackedEnumState::value(VoteStatus::Draft));
    }

    #[Test]
    public function test_aceita_string_ou_null(): void
    {
        $this->assertSame('closed', FormatBackedEnumState::value('closed'));
        $this->assertSame('', FormatBackedEnumState::value(null));
    }
}
