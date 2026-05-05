<?php

namespace App\Filament\Admin\Resources\Tasks\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaskHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'history';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('task-history.plural_label');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('action')
                    ->label(__('task-history.fields.action'))
                    ->badge(),
                TextColumn::make('user.email')
                    ->label(__('task-history.fields.user'))
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

