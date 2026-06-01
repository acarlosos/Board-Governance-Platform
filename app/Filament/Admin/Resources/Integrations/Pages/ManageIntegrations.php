<?php

namespace App\Filament\Admin\Resources\Integrations\Pages;

use App\Actions\Integrations\PersistIntegrationAction;
use App\Filament\Admin\Resources\Integrations\IntegrationResource;
use App\Support\Filament\RemapValidationToMountedAction;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Contracts\HasSchemas;

class ManageIntegrations extends ManageRecords
{
    protected static string $resource = IntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('actions.create'))
                ->using(fn (array $data, HasActions&HasSchemas $livewire) => RemapValidationToMountedAction::run(
                    fn () => app(PersistIntegrationAction::class)->create(auth()->user(), $data),
                    $livewire,
                )),
        ];
    }
}
