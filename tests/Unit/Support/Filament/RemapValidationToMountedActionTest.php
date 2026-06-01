<?php

namespace Tests\Unit\Support\Filament;

use App\Support\Filament\RemapValidationToMountedAction;
use Filament\Actions\Contracts\HasActions;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

final class RemapValidationToMountedActionTest extends TestCase
{
    public function test_mapeia_chaves_simples_para_mounted_actions_data(): void
    {
        $exception = ValidationException::withMessages([
            'password' => ['A senha é fraca.'],
            'roles' => ['Obrigatório.'],
        ]);

        $mapped = RemapValidationToMountedAction::messagesForIndex($exception, 1);

        $this->assertSame(['A senha é fraca.'], $mapped['mountedActions.1.data.password']);
        $this->assertSame(['Obrigatório.'], $mapped['mountedActions.1.data.roles']);
    }

    public function test_nao_duplica_prefixo_se_ja_vier_mapeado(): void
    {
        $exception = ValidationException::withMessages([
            'mountedActions.0.data.email' => ['Inválido.'],
        ]);

        $mapped = RemapValidationToMountedAction::messagesForIndex($exception, 0);

        $this->assertSame(['Inválido.'], $mapped['mountedActions.0.data.email']);
    }

    public function test_resolve_action_index_aceita_chave_string_numerica(): void
    {
        $livewire = Mockery::mock(HasActions::class);
        $livewire->mountedActions = ['0' => ['name' => 'create']];

        $exception = ValidationException::withMessages([
            'title' => ['Obrigatório.'],
        ]);

        $mapped = RemapValidationToMountedAction::messages($exception, $livewire);

        $this->assertSame(['Obrigatório.'], $mapped['mountedActions.0.data.title']);
    }

    public function test_run_remapeia_e_relanca_validation_exception(): void
    {
        $livewire = Mockery::mock(HasActions::class);
        $livewire->mountedActions = [0 => []];

        try {
            RemapValidationToMountedAction::run(function (): void {
                throw ValidationException::withMessages([
                    'title' => ['O título é obrigatório.'],
                ]);
            }, $livewire);
            $this->fail('Esperava ValidationException relançada.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['O título é obrigatório.'],
                $exception->errors()['mountedActions.0.data.title'],
            );
        }
    }
}
