<?php

namespace App\Filament\Admin\Resources\Boards\Pages;

use App\Actions\Boards\PersistBoardAction;
use App\Filament\Admin\Resources\Boards\BoardResource;
use App\Models\Board;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;

class ManageBoards extends ManageRecords
{
    protected static string $resource = BoardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('actions.create'))
                ->modalWidth(Width::FiveExtraLarge)
                ->using(function (array $data, HasActions & HasSchemas $livewire): Board {
                    return app(PersistBoardAction::class)->create(auth()->user(), $data);
                }),
        ];
    }
}

