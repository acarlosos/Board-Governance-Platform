<?php

namespace App\Filament\Admin\Resources\Signatures\Pages;

use App\Actions\Signatures\PersistSignatureRequestAction;
use App\Filament\Admin\Resources\Signatures\SignatureRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSignatureRequests extends ManageRecords
{
    protected static string $resource = SignatureRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('actions.create'))
                ->using(fn (array $data) => app(PersistSignatureRequestAction::class)->create(auth()->user(), $data)),
        ];
    }
}

