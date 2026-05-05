<?php

namespace App\Filament\Admin\Resources\Votes\RelationManagers;

use App\Enums\VoteType;
use App\Models\User;
use App\Models\Vote;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VoteResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'responses';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        /** @var Vote $vote */
        $vote = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->latest('voted_at'))
            ->columns([
                TextColumn::make('option.title')
                    ->label(__('vote-responses.fields.option'))
                    ->toggleable(),

                TextColumn::make('user.email')
                    ->label(__('vote-responses.fields.user'))
                    ->visible(function () use ($vote): bool {
                        $user = auth()->user();
                        if (! $user instanceof User) {
                            return false;
                        }

                        if ($vote->type !== VoteType::Secret) {
                            return true;
                        }

                        // Em votação secreta, só super_admin vê identidade.
                        return $user->isSuperAdmin();
                    })
                    ->toggleable(),

                TextColumn::make('voted_at')
                    ->label(__('vote-responses.fields.voted_at'))
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}

