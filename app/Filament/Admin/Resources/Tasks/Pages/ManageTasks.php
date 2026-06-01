<?php

namespace App\Filament\Admin\Resources\Tasks\Pages;

use App\Actions\Tasks\PersistTaskAction;
use App\Filament\Admin\Resources\Tasks\TaskResource;
use App\Support\Filament\RemapValidationToMountedAction;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Contracts\HasSchemas;

class ManageTasks extends ManageRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('actions.create'))
                ->using(fn (array $data, HasActions&HasSchemas $livewire) => RemapValidationToMountedAction::run(
                    fn () => app(PersistTaskAction::class)->create(auth()->user(), $data),
                    $livewire,
                )),
        ];
    }
}
