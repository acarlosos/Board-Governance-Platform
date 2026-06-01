<?php

namespace App\Support\Filament;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Schemas\Components\Livewire;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

final class RelationManagerModalAction
{
    /**
     * @param  class-string  $relationManager
     * @param  class-string  $pageClass
     * @param  callable(Model): bool  $visible
     * @param  callable(Model): string|null  $recordTitle
     */
    public static function make(
        string $name,
        string $label,
        string $relationManager,
        string|BackedEnum|Htmlable $icon,
        string $pageClass,
        callable $visible,
        ?callable $recordTitle = null,
    ): Action {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->visible($visible)
            ->modalWidth(Width::SevenExtraLarge)
            ->modalHeading(function (Model $record) use ($label, $recordTitle): string {
                $title = $recordTitle !== null
                    ? ($recordTitle($record) ?? '')
                    : (string) ($record->getAttribute('title') ?? $record->getKey());

                return $label.' — '.$title;
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('actions.cancel'))
            ->schema(fn (Model $record): array => [
                Livewire::make($relationManager, [
                    'ownerRecord' => $record,
                    'pageClass' => $pageClass,
                ]),
            ])
            ->action(function (): void {});
    }
}
