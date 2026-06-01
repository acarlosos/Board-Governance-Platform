<?php

namespace App\Support\Filament;

use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Illuminate\Validation\ValidationException;

/**
 * Erros de {@see ValidationException} vindos de Actions/API (chaves simples, ex. password)
 * têm de ser re-mapeados para o statePath do modal Filament (mountedActions.N.data.*).
 */
final class RemapValidationToMountedAction
{
    /**
     * @return array<string, list<string>>
     */
    public static function messages(ValidationException $exception, HasActions $livewire, ?Action $action = null): array
    {
        $index = self::resolveActionIndex($livewire, $action);

        if ($index === null) {
            return $exception->errors();
        }

        return self::messagesForIndex($exception, $index);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function messagesForIndex(ValidationException $exception, int $index): array
    {
        $prefix = "mountedActions.{$index}.data.";

        $mapped = [];

        foreach ($exception->errors() as $field => $messages) {
            $key = str_starts_with($field, 'mountedActions.')
                ? $field
                : $prefix.$field;

            $mapped[$key] = $messages;
        }

        return $mapped;
    }

    /**
     * Executa callback de Action Filament e re-mapeia erros de validação para o modal.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function run(callable $callback, HasActions $livewire, ?Action $action = null): mixed
    {
        try {
            return $callback();
        } catch (ValidationException $exception) {
            self::throw($exception, $livewire, $action);
        }
    }

    /**
     * @return never
     */
    public static function throw(ValidationException $exception, HasActions $livewire, ?Action $action = null): void
    {
        $index = self::resolveActionIndex($livewire, $action);

        if ($index === null) {
            NotifyActionValidation::send($exception);

            throw $exception;
        }

        $mapped = self::messagesForIndex($exception, $index);

        NotifyActionValidation::send($exception);

        throw ValidationException::withMessages($mapped);
    }

    private static function resolveActionIndex(HasActions $livewire, ?Action $action = null): ?int
    {
        if ($action?->getNestingIndex() !== null) {
            return $action->getNestingIndex();
        }

        if (method_exists($livewire, 'getMountedAction')) {
            $mounted = $livewire->getMountedAction();

            if ($mounted?->getNestingIndex() !== null) {
                return $mounted->getNestingIndex();
            }
        }

        $mountedActions = $livewire->mountedActions ?? [];

        if ($mountedActions === []) {
            return null;
        }

        $index = array_key_last($mountedActions);

        return is_numeric($index) ? (int) $index : null;
    }
}
