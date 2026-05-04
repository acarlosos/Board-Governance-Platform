<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Actions\Filament\PersistPanelUserAction;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\Width;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Database\Eloquent\Model;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('actions.create'))
                ->modalWidth(Width::FiveExtraLarge)
                ->using(function (array $data, HasActions & HasSchemas $livewire): User {
                    return app(PersistPanelUserAction::class)->create(auth()->user(), $data);
                }),
        ];
    }
}
