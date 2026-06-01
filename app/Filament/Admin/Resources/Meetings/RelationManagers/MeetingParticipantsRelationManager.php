<?php

namespace App\Filament\Admin\Resources\Meetings\RelationManagers;

use App\Actions\Meetings\PersistMeetingParticipantAction;
use App\Enums\MeetingParticipantRole;
use App\Enums\MeetingParticipantStatus;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Support\Filament\RemapValidationToMountedAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MeetingParticipantsRelationManager extends RelationManager
{
    protected static string $relationship = 'participants';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('meeting-participants.section_main');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('view', $ownerRecord);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.email')
                    ->label(__('meeting-participants.fields.user'))
                    ->searchable(),
                TextColumn::make('role')
                    ->label(__('meeting-participants.fields.role'))
                    ->formatStateUsing(fn (MeetingParticipantRole|string $state): string => __('meeting-participants.roles.'.($state instanceof MeetingParticipantRole ? $state->value : $state)))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('meeting-participants.fields.status'))
                    ->formatStateUsing(fn (MeetingParticipantStatus|string $state): string => __('meeting-participants.status.'.($state instanceof MeetingParticipantStatus ? $state->value : $state)))
                    ->badge(),
                TextColumn::make('responded_at')
                    ->label(__('meeting-participants.fields.responded_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('actions.create'))
                    ->modalWidth(Width::FiveExtraLarge)
                    ->form($this->formSchema())
                    ->using(fn (array $data): Model => RemapValidationToMountedAction::run(function () use ($data): Model {
                        /** @var Meeting $meeting */
                        $meeting = $this->getOwnerRecord();

                        return app(PersistMeetingParticipantAction::class)->create(auth()->user(), $meeting, $data);
                    }, $this)),
            ])
            ->actions([
                EditAction::make()
                    ->label(__('actions.edit'))
                    ->modalWidth(Width::FiveExtraLarge)
                    ->form($this->formSchema(isEdit: true))
                    ->using(fn (array $data, Model $record): Model => RemapValidationToMountedAction::run(function () use ($data, $record): Model {
                        /** @var MeetingParticipant $record */

                        return app(PersistMeetingParticipantAction::class)->update(auth()->user(), $record, $data);
                    }, $this)),
                DeleteAction::make()
                    ->label(__('actions.delete'))
                    ->using(function (Model $record): void {
                        /** @var MeetingParticipant $record */
                        app(PersistMeetingParticipantAction::class)->remove(auth()->user(), $record);
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
    private function formSchema(bool $isEdit = false): array
    {
        /** @var Meeting $meeting */
        $meeting = $this->getOwnerRecord();

        $roleOptions = collect(MeetingParticipantRole::cases())->mapWithKeys(fn (MeetingParticipantRole $r) => [$r->value => __('meeting-participants.roles.'.$r->value)])->all();
        $statusOptions = collect(MeetingParticipantStatus::cases())->mapWithKeys(fn (MeetingParticipantStatus $s) => [$s->value => __('meeting-participants.status.'.$s->value)])->all();

        return [
            Select::make('user_id')
                ->label(__('meeting-participants.fields.user'))
                ->relationship(
                    name: 'user',
                    titleAttribute: 'email',
                    modifyQueryUsing: function (Builder $query) use ($meeting, $isEdit): void {
                        $query->where('tenant_id', $meeting->tenant_id);

                        if (! $isEdit) {
                            $query->whereNotIn('id', MeetingParticipant::activeUserIdsForMeetingSubquery($meeting));
                        }
                    },
                )
                ->searchable()
                ->preload()
                ->noOptionsMessage(__('meeting-participants.messages.no_users_available'))
                ->noSearchResultsMessage(__('meeting-participants.messages.no_users_search_results'))
                ->required()
                ->disabled($isEdit),
            Select::make('role')
                ->label(__('meeting-participants.fields.role'))
                ->options($roleOptions)
                ->required()
                ->native(false),
            Select::make('status')
                ->label(__('meeting-participants.fields.status'))
                ->options($statusOptions)
                ->default(MeetingParticipantStatus::Invited->value)
                ->required()
                ->native(false),
            DateTimePicker::make('responded_at')
                ->label(__('meeting-participants.fields.responded_at'))
                ->seconds(false),
        ];
    }
}
