<?php

namespace App\Filament\Admin\Resources\Votes\RelationManagers;

use App\Actions\Votes\PersistVoteOptionAction;
use App\Enums\VoteStatus;
use App\Models\Vote;
use App\Models\VoteOption;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VoteOptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('order_column')
            ->columns([
                TextColumn::make('title')
                    ->label(__('vote-options.fields.title'))
                    ->searchable(),
                TextColumn::make('order_column')
                    ->label(__('vote-options.fields.order'))
                    ->sortable()
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('actions.create'))
                    ->visible(function (): bool {
                        /** @var Vote $vote */
                        $vote = $this->getOwnerRecord();
                        return $vote->status === VoteStatus::Draft;
                    })
                    ->form($this->formSchema())
                    ->using(function (array $data): VoteOption {
                        /** @var Vote $vote */
                        $vote = $this->getOwnerRecord();
                        return app(PersistVoteOptionAction::class)->create(auth()->user(), $vote, $data);
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->label(__('actions.edit'))
                    ->visible(fn (VoteOption $record): bool => $record->vote->status === VoteStatus::Draft)
                    ->form($this->formSchema())
                    ->using(fn (VoteOption $record, array $data): VoteOption => app(PersistVoteOptionAction::class)->update(auth()->user(), $record, $data)),
                DeleteAction::make()
                    ->label(__('actions.delete'))
                    ->visible(fn (VoteOption $record): bool => $record->vote->status === VoteStatus::Draft),
            ]);
    }

    private function formSchema(): array
    {
        return [
            TextInput::make('title')
                ->label(__('vote-options.fields.title'))
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label(__('vote-options.fields.description')),
            TextInput::make('order_column')
                ->label(__('vote-options.fields.order'))
                ->numeric()
                ->minValue(0),
        ];
    }
}

