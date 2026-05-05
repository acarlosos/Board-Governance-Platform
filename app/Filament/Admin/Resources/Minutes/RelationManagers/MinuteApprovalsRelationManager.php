<?php

namespace App\Filament\Admin\Resources\Minutes\RelationManagers;

use App\Models\MinuteApproval;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MinuteApprovalsRelationManager extends RelationManager
{
    protected static string $relationship = 'approvals';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.email')
                    ->label(__('minute-approvals.fields.user'))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('minute-approvals.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('minute-approvals.status.'.((string) $state))),
                TextColumn::make('approved_at')
                    ->label(__('minute-approvals.fields.approved_at'))
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('rejected_at')
                    ->label(__('minute-approvals.fields.rejected_at'))
                    ->dateTime()
                    ->toggleable(),
            ])
            ->defaultSort('id');
    }
}

