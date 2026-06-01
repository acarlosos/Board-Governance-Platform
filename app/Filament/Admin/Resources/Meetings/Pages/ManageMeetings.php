<?php

namespace App\Filament\Admin\Resources\Meetings\Pages;

use App\Actions\Meetings\PersistMeetingAction;
use App\Filament\Admin\Resources\Meetings\MeetingResource;
use App\Models\Meeting;
use App\Support\Filament\RemapValidationToMountedAction;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;

class ManageMeetings extends ManageRecords
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('actions.create'))
                ->modalWidth(Width::FiveExtraLarge)
                ->using(fn (array $data, HasActions&HasSchemas $livewire): Meeting => RemapValidationToMountedAction::run(
                    fn (): Meeting => app(PersistMeetingAction::class)->create(auth()->user(), $data),
                    $livewire,
                )),
        ];
    }
}
