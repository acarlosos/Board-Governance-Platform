<?php

namespace App\Filament\Admin\Resources\Meetings;

use App\Actions\Meetings\CancelMeetingAction;
use App\Actions\Meetings\CompleteMeetingAction;
use App\Actions\Meetings\PersistMeetingAction;
use App\Actions\Meetings\StartMeetingAction;
use App\Enums\MeetingStatus;
use App\Filament\Admin\Resources\Meetings\Pages\ManageMeetings;
use App\Filament\Admin\Resources\Meetings\RelationManagers\MeetingAgendaItemsRelationManager;
use App\Filament\Admin\Resources\Meetings\RelationManagers\MeetingParticipantsRelationManager;
use App\Models\Board;
use App\Models\Meeting;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): ?string
    {
        return __('meetings.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('meetings.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('meetings.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('meetings.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        $sectionLayout = static fn (Section $section): Section => $section
            ->extraAttributes(['class' => 'w-full min-w-0'])
            ->columnSpanFull()
            ->grow()
            ->contained(false)
            ->columns(2);

        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'w-full min-w-0'])
            ->components([
                $sectionLayout(Section::make(__('meetings.section_main')))
                    ->components([
                        TextInput::make('title')
                            ->label(__('meetings.fields.title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label(__('meetings.fields.description'))
                            ->rows(4)
                            ->columnSpanFull(),
                        Select::make('status')
                            ->label(__('meetings.fields.status'))
                            ->options(self::meetingStatusOptions())
                            ->default(MeetingStatus::Draft->value)
                            ->required()
                            ->native(false),
                    ]),
                $sectionLayout(Section::make(__('meetings.section_board')))
                    ->components([
                        Select::make('board_id')
                            ->label(__('meetings.fields.board'))
                            ->relationship(
                                name: 'board',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $q): Builder {
                                    $user = auth()->user();
                                    if (! $user) {
                                        return $q;
                                    }
                                    if ($user->isSuperAdmin()) {
                                        return $q;
                                    }
                                    return $q->where('tenant_id', $user->tenant_id);
                                },
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                    ]),
                $sectionLayout(Section::make(__('meetings.section_dates')))
                    ->components([
                        DateTimePicker::make('scheduled_at')
                            ->label(__('meetings.fields.scheduled_at'))
                            ->required()
                            ->seconds(false)
                            ->columnSpanFull(),
                        DateTimePicker::make('starts_at')
                            ->label(__('meetings.fields.starts_at'))
                            ->seconds(false),
                        DateTimePicker::make('ends_at')
                            ->label(__('meetings.fields.ends_at'))
                            ->seconds(false),
                    ]),
                $sectionLayout(Section::make(__('meetings.section_video')))
                    ->components([
                        TextInput::make('video_conference_url')
                            ->label(__('meetings.fields.video_conference_url'))
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull(),
                    ]),
                $sectionLayout(Section::make(__('meetings.section_organization')))
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                    ->components([
                        Select::make('tenant_id')
                            ->label(__('meetings.fields.tenant'))
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function meetingStatusOptions(): array
    {
        $out = [];
        foreach (MeetingStatus::cases() as $case) {
            $out[$case->value] = __('meetings.status.'.$case->value);
        }

        return $out;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('meetings.fields.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('board.name')
                    ->label(__('meetings.fields.board'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->label(__('meetings.fields.tenant'))
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('scheduled_at')
                    ->label(__('meetings.fields.scheduled_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('meetings.fields.status'))
                    ->formatStateUsing(fn (MeetingStatus $state): string => __('meetings.status.'.$state->value))
                    ->badge()
                    ->sortable(),
                TextColumn::make('participants_count')
                    ->label(__('meetings.fields.participants'))
                    ->counts('participants')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('meetings.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                SelectFilter::make('tenant_id')
                    ->label(__('meetings.filters.tenant'))
                    ->relationship('tenant', 'name')
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                SelectFilter::make('board_id')
                    ->label(__('meetings.filters.board'))
                    ->relationship('board', 'name'),
                SelectFilter::make('status')
                    ->label(__('meetings.filters.status'))
                    ->options(self::meetingStatusOptions()),
                Filter::make('scheduled_period')
                    ->label(__('meetings.filters.period'))
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label(__('meetings.filters.from'))
                            ->native(false),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label(__('meetings.filters.until'))
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $from = filled($data['from'] ?? null) ? Carbon::parse($data['from'])->startOfDay() : null;
                        $until = filled($data['until'] ?? null) ? Carbon::parse($data['until'])->endOfDay() : null;

                        return $query
                            ->when($from, fn (Builder $q) => $q->where('scheduled_at', '>=', $from))
                            ->when($until, fn (Builder $q) => $q->where('scheduled_at', '<=', $until));
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('actions.edit'))
                    ->modalWidth(Width::FiveExtraLarge)
                    ->using(function (array $data, HasSchemas $livewire, Model $record): void {
                        /** @var Meeting $record */
                        app(PersistMeetingAction::class)->update(auth()->user(), $record, $data);
                    }),
                \Filament\Actions\Action::make('start')
                    ->label(__('meetings.actions.start'))
                    ->icon(Heroicon::OutlinedPlay)
                    ->visible(fn (Meeting $record): bool => $record->status === MeetingStatus::Scheduled)
                    ->requiresConfirmation()
                    ->action(fn (Meeting $record) => app(StartMeetingAction::class)->start(auth()->user(), $record)),
                \Filament\Actions\Action::make('complete')
                    ->label(__('meetings.actions.complete'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->visible(fn (Meeting $record): bool => $record->status === MeetingStatus::InProgress)
                    ->requiresConfirmation()
                    ->action(fn (Meeting $record) => app(CompleteMeetingAction::class)->complete(auth()->user(), $record)),
                \Filament\Actions\Action::make('cancel')
                    ->label(__('meetings.actions.cancel'))
                    ->icon(Heroicon::OutlinedXCircle)
                    ->visible(fn (Meeting $record): bool => in_array($record->status, [MeetingStatus::Draft, MeetingStatus::Scheduled], true))
                    ->requiresConfirmation()
                    ->action(fn (Meeting $record) => app(CancelMeetingAction::class)->cancel(auth()->user(), $record)),
                DeleteAction::make()->label(__('actions.delete')),
                RestoreAction::make()->label(__('actions.restore')),
                ForceDeleteAction::make()->label(__('actions.force_delete')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('actions.delete_bulk')),
                    RestoreBulkAction::make()->label(__('actions.restore_bulk')),
                    ForceDeleteBulkAction::make()->label(__('actions.force_delete_bulk')),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();
        if (! $user) {
            return $query;
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        // `board_member` pode visualizar, mas não ter listagem ampla por permissões herdadas.
        if (($user->hasRole('tenant_admin') || $user->can('manage_meetings')) && ! $user->hasRole('board_member')) {
            return $query;
        }

        // board_member / participante: só vê reuniões do board onde é membro ativo ou onde participa
        $query->where(function (Builder $q) use ($user): void {
            $q->whereHas('board.boardMembers', fn (Builder $bm) => $bm->where('user_id', $user->id)->where('status', 'active'))
                ->orWhereHas('participants', fn (Builder $p) => $p->where('user_id', $user->id));
        });

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            MeetingParticipantsRelationManager::class,
            MeetingAgendaItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMeetings::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

