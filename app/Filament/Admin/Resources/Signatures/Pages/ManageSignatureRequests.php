<?php

namespace App\Filament\Admin\Resources\Signatures\Pages;

use App\Actions\Signatures\PersistSignatureRequestAction;
use App\Filament\Admin\Resources\Signatures\SignatureRequestResource;
use App\Support\Filament\RemapValidationToMountedAction;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Contracts\HasSchemas;

class ManageSignatureRequests extends ManageRecords
{
    protected static string $resource = SignatureRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('actions.create'))
                ->using(fn (array $data, HasActions&HasSchemas $livewire) => RemapValidationToMountedAction::run(
                    fn () => app(PersistSignatureRequestAction::class)->create(auth()->user(), $data),
                    $livewire,
                )),
        ];
    }
}
