<?php

namespace App\Filament\Admin\Resources\Tasks\Pages;

use App\Actions\Tasks\PersistTaskAction;
use App\Filament\Admin\Resources\Tasks\TaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTasks extends ManageRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('actions.create'))
                ->using(fn (array $data) => app(PersistTaskAction::class)->create(auth()->user(), $data)),
        ];
    }
}

