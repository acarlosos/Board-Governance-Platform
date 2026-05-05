<?php

namespace App\Filament\Admin\Resources\Integrations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IntegrationLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('integration-logs.plural_label');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('action')
                    ->label(__('integration-logs.fields.action'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('integration-logs.fields.status'))
                    ->badge(),
                TextColumn::make('message')
                    ->label(__('integration-logs.fields.message'))
                    ->wrap()
                    ->limit(120)
                    ->toggleable(),
                TextColumn::make('user.email')
                    ->label(__('integration-logs.fields.user'))
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}

