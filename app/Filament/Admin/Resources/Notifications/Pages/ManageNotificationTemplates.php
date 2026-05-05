<?php

namespace App\Filament\Admin\Resources\Notifications\Pages;

use App\Actions\Notifications\PersistNotificationTemplateAction;
use App\Filament\Admin\Resources\Notifications\NotificationTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageNotificationTemplates extends ManageRecords
{
    protected static string $resource = NotificationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('actions.create'))
                ->using(fn (array $data) => app(PersistNotificationTemplateAction::class)->create(auth()->user(), $data)),
        ];
    }
}

