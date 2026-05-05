<?php

namespace App\Filament\Admin\Resources\Documents\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentAccessLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'accessLogs';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('action')
                    ->label(__('document-access-logs.fields.action'))
                    ->formatStateUsing(fn ($state): string => match ((string) $state) {
                        'viewed' => __('document-access-logs.actions.viewed'),
                        'downloaded' => __('document-access-logs.actions.downloaded'),
                        default => (string) $state,
                    })
                    ->badge(),
                TextColumn::make('user.email')
                    ->label(__('document-access-logs.fields.user'))
                    ->toggleable(),
                TextColumn::make('ip_address')
                    ->label(__('document-access-logs.fields.ip_address'))
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

