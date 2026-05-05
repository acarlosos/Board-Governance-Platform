<?php

namespace App\Filament\Admin\Resources\Signatures\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SignatureEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('signature-events.plural_label');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('action')
                    ->label(__('signature-events.fields.action'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('signature-events.fields.status'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('user.email')
                    ->label(__('signature-events.fields.user'))
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

