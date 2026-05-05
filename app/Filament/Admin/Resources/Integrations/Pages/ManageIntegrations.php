<?php

namespace App\Filament\Admin\Resources\Integrations\Pages;

use App\Actions\Integrations\PersistIntegrationAction;
use App\Filament\Admin\Resources\Integrations\IntegrationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageIntegrations extends ManageRecords
{
    protected static string $resource = IntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('actions.create'))
                ->using(fn (array $data) => app(PersistIntegrationAction::class)->create(auth()->user(), $data)),
        ];
    }
}

