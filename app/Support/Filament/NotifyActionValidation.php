<?php

namespace App\Support\Filament;

use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Erros de Actions em ações de tabela/modal sem formulário montado não aparecem no UI
 * se a {@see ValidationException} for relançada sem remapeamento — notificar explicitamente.
 */
final class NotifyActionValidation
{
    public static function send(ValidationException $exception): void
    {
        $message = collect($exception->errors())
            ->flatten()
            ->filter(fn (mixed $m): bool => is_string($m) && $m !== '')
            ->first();

        Notification::make()
            ->title(is_string($message) ? $message : __('validation.failed'))
            ->danger()
            ->send();
    }
}
