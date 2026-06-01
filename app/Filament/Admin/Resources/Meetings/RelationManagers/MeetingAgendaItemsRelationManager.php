<?php

namespace App\Filament\Admin\Resources\Meetings\RelationManagers;

use App\Actions\Meetings\PersistMeetingAgendaItemAction;
use App\Enums\MeetingAgendaItemStatus;
use App\Models\Meeting;
use App\Models\MeetingAgendaItem;
use App\Support\Filament\RemapValidationToMountedAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MeetingAgendaItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'agendaItems';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('meeting-agenda-items.section_main');
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
            ->defaultSort('order_column')
            ->columns([
                TextColumn::make('order_column')
                    ->label(__('meeting-agenda-items.fields.order_column'))
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('meeting-agenda-items.fields.title'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('meeting-agenda-items.fields.status'))
                    ->formatStateUsing(fn (MeetingAgendaItemStatus|string $state): string => __('meeting-agenda-items.status.'.($state instanceof MeetingAgendaItemStatus ? $state->value : $state)))
                    ->badge(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('actions.create'))
                    ->modalWidth(Width::FiveExtraLarge)
                    ->form($this->formSchema())
                    ->using(fn (array $data): Model => RemapValidationToMountedAction::run(function () use ($data): Model {
                        /** @var Meeting $meeting */
                        $meeting = $this->getOwnerRecord();

                        return app(PersistMeetingAgendaItemAction::class)->create(auth()->user(), $meeting, $data);
                    }, $this)),
            ])
            ->actions([
                EditAction::make()
                    ->label(__('actions.edit'))
                    ->modalWidth(Width::FiveExtraLarge)
                    ->form($this->formSchema())
                    ->using(fn (array $data, Model $record): Model => RemapValidationToMountedAction::run(function () use ($data, $record): Model {
                        /** @var MeetingAgendaItem $record */

                        return app(PersistMeetingAgendaItemAction::class)->update(auth()->user(), $record, $data);
                    }, $this)),
                DeleteAction::make()
                    ->label(__('actions.delete'))
                    ->using(function (Model $record): void {
                        /** @var MeetingAgendaItem $record */
                        app(PersistMeetingAgendaItemAction::class)->remove(auth()->user(), $record);
                    }),
            ])
            ->modifyQueryUsing(function (Builder $query): Builder {
                /** @var Meeting $meeting */
                $meeting = $this->getOwnerRecord();

                return $query->where('tenant_id', $meeting->tenant_id);
            });
    }

    /**
     * @return array<int, Component>
     */
    private function formSchema(): array
    {
        $statusOptions = collect(MeetingAgendaItemStatus::cases())->mapWithKeys(fn (MeetingAgendaItemStatus $s) => [$s->value => __('meeting-agenda-items.status.'.$s->value)])->all();

        return [
            TextInput::make('title')
                ->label(__('meeting-agenda-items.fields.title'))
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Textarea::make('description')
                ->label(__('meeting-agenda-items.fields.description'))
                ->rows(4)
                ->columnSpanFull(),
            TextInput::make('order_column')
                ->label(__('meeting-agenda-items.fields.order_column'))
                ->numeric()
                ->default(0)
                ->required(),
            Select::make('status')
                ->label(__('meeting-agenda-items.fields.status'))
                ->options($statusOptions)
                ->default(MeetingAgendaItemStatus::Pending->value)
                ->required()
                ->native(false),
        ];
    }
}
