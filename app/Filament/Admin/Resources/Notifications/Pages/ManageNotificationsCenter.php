<?php

namespace App\Filament\Admin\Resources\Notifications\Pages;

use App\Filament\Admin\Resources\Notifications\NotificationCenterResource;
use Filament\Resources\Pages\ManageRecords;

class ManageNotificationsCenter extends ManageRecords
{
    protected static string $resource = NotificationCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

