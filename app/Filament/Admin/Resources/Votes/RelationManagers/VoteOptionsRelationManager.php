<?php

namespace App\Filament\Admin\Resources\Votes\RelationManagers;

use App\Actions\Votes\PersistVoteOptionAction;
use App\Enums\VoteStatus;
use App\Models\Vote;
use App\Models\VoteOption;
use App\Support\Filament\RemapValidationToMountedAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VoteOptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('vote-options.section_main');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('view', $ownerRecord);
    }

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
                    ->modalWidth(Width::FiveExtraLarge)
                    ->visible(function (): bool {
                        /** @var Vote $vote */
                        $vote = $this->getOwnerRecord();

                        return $vote->status === VoteStatus::Draft;
                    })
                    ->form($this->formSchema(forCreate: true))
                    ->using(fn (array $data): VoteOption => RemapValidationToMountedAction::run(function () use ($data): VoteOption {
                        /** @var Vote $vote */
                        $vote = $this->getOwnerRecord();

                        return app(PersistVoteOptionAction::class)->create(auth()->user(), $vote, $data);
                    }, $this)),
            ])
            ->actions([
                EditAction::make()
                    ->label(__('actions.edit'))
                    ->visible(fn (VoteOption $record): bool => $record->vote->status === VoteStatus::Draft)
                    ->form($this->formSchema())
                    ->using(fn (VoteOption $record, array $data): VoteOption => RemapValidationToMountedAction::run(
                        fn (): VoteOption => app(PersistVoteOptionAction::class)->update(auth()->user(), $record, $data),
                        $this,
                    )),
                DeleteAction::make()
                    ->label(__('actions.delete'))
                    ->visible(fn (VoteOption $record): bool => $record->vote->status === VoteStatus::Draft),
            ]);
    }

    /**
     * @return array<int, TextInput|Textarea>
     */
    private function formSchema(bool $forCreate = false): array
    {
        $orderColumn = TextInput::make('order_column')
            ->label(__('vote-options.fields.order'))
            ->numeric()
            ->minValue(0)
            ->required();

        if ($forCreate) {
            $orderColumn->default(fn (): int => $this->nextOrderColumn());
        }

        return [
            TextInput::make('title')
                ->label(__('vote-options.fields.title'))
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label(__('vote-options.fields.description')),
            $orderColumn,
        ];
    }

    private function nextOrderColumn(): int
    {
        /** @var Vote $vote */
        $vote = $this->getOwnerRecord();

        if ($vote->options()->count() === 0) {
            return 1;
        }

        $max = $vote->options()->max('order_column');

        return $max !== null ? (int) $max + 1 : 1;
    }
}
