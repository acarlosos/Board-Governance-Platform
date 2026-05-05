<?php

namespace App\Filament\Admin\Resources\AuditLogs\Pages;

use App\Filament\Admin\Resources\AuditLogs\AuditLogResource;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;

class ManageAuditLogs extends ManageRecords
{
    protected static string $resource = AuditLogResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('audit.plural_label');
    }

    public function getHeading(): string|Htmlable|null
    {
        return __('audit.plural_label');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}

