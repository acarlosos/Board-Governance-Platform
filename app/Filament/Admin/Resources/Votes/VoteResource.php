<?php

namespace App\Filament\Admin\Resources\Votes;

use App\Actions\Votes\CancelVoteAction;
use App\Actions\Votes\CastVoteAction;
use App\Actions\Votes\CloseVoteAction;
use App\Actions\Votes\OpenVoteAction;
use App\Actions\Votes\PersistVoteAction;
use App\Enums\VoteStatus;
use App\Enums\VoteType;
use App\Filament\Admin\Resources\Votes\Pages\ManageVotes;
use App\Filament\Admin\Resources\Votes\RelationManagers\VoteOptionsRelationManager;
use App\Filament\Admin\Resources\Votes\RelationManagers\VoteResponsesRelationManager;
use App\Models\User;
use App\Models\Vote;
use BackedEnum;
use Filament\Actions\Action;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VoteResource extends Resource
{
    protected static ?string $model = Vote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHandRaised;

    protected static ?int $navigationSort = 60;

    public static function getNavigationGroup(): ?string
    {
        return __('votes.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('votes.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('votes.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('votes.navigation_label');
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
                $sectionLayout(Section::make(__('votes.sections.data')))
                    ->components([
                        TextInput::make('title')
                            ->label(__('votes.fields.title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label(__('votes.fields.description'))
                            ->columnSpanFull(),
                        Select::make('type')
                            ->label(__('votes.fields.type'))
                            ->required()
                            ->options([
                                VoteType::Open->value => __('votes.types.open'),
                                VoteType::Secret->value => __('votes.types.secret'),
                            ]),
                        TextInput::make('status')
                            ->label(__('votes.fields.status'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?Vote $record): bool => (bool) $record)
                            ->formatStateUsing(fn (?Vote $record): string => $record ? __('votes.status.'.$record->status->value) : __('votes.status.draft')),
                    ]),

                $sectionLayout(Section::make(__('votes.sections.meeting')))
                    ->components([
                        Select::make('meeting_id')
                            ->label(__('votes.fields.meeting'))
                            ->relationship(
                                name: 'meeting',
                                titleAttribute: 'title',
                                modifyQueryUsing: function (Builder $query): Builder {
                                    $user = auth()->user();
                                    if (! $user instanceof User || $user->isSuperAdmin()) {
                                        return $query;
                                    }

                                    return $query->where('tenant_id', $user->tenant_id);
                                },
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                    ]),

                $sectionLayout(Section::make(__('votes.sections.configuration')))
                    ->components([
                        TextInput::make('quorum_required')
                            ->label(__('votes.fields.quorum_required'))
                            ->numeric()
                            ->minValue(1),
                        DateTimePicker::make('starts_at')
                            ->label(__('votes.fields.starts_at')),
                        DateTimePicker::make('ends_at')
                            ->label(__('votes.fields.ends_at')),
                    ]),

                $sectionLayout(Section::make(__('votes.sections.organization')))
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() === true)
                    ->components([
                        TextInput::make('tenant_id')
                            ->label(__('fields.tenant'))
                            ->numeric()
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('votes.fields.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('meeting.title')
                    ->label(__('votes.fields.meeting'))
                    ->toggleable(),
                TextColumn::make('type')
                    ->label(__('votes.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('votes.types.'.((string) $state))),
                TextColumn::make('status')
                    ->label(__('votes.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('votes.status.'.((string) $state))),
                TextColumn::make('responses_count')
                    ->label(__('votes.fields.responses'))
                    ->counts('responses')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('votes.filters.status'))
                    ->options([
                        VoteStatus::Draft->value => __('votes.status.draft'),
                        VoteStatus::Open->value => __('votes.status.open'),
                        VoteStatus::Closed->value => __('votes.status.closed'),
                        VoteStatus::Cancelled->value => __('votes.status.cancelled'),
                    ]),
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->label(__('actions.edit'))
                    ->visible(fn (Vote $record): bool => $record->status === VoteStatus::Draft)
                    ->using(function (Vote $record, array $data): Vote {
                        $data['status'] = VoteStatus::Draft->value;
                        return app(PersistVoteAction::class)->update(auth()->user(), $record, $data);
                    }),

                Action::make('open')
                    ->label(__('votes.actions.open'))
                    ->icon(Heroicon::OutlinedPlay)
                    ->visible(fn (Vote $record): bool => $record->status === VoteStatus::Draft)
                    ->requiresConfirmation()
                    ->action(fn (Vote $record) => app(OpenVoteAction::class)->open(auth()->user(), $record)),

                Action::make('close')
                    ->label(__('votes.actions.close'))
                    ->icon(Heroicon::OutlinedStop)
                    ->visible(fn (Vote $record): bool => $record->status === VoteStatus::Open)
                    ->requiresConfirmation()
                    ->action(fn (Vote $record) => app(CloseVoteAction::class)->close(auth()->user(), $record)),

                Action::make('cancel')
                    ->label(__('votes.actions.cancel'))
                    ->icon(Heroicon::OutlinedXCircle)
                    ->visible(fn (Vote $record): bool => in_array($record->status, [VoteStatus::Draft, VoteStatus::Open], true))
                    ->requiresConfirmation()
                    ->action(fn (Vote $record) => app(CancelVoteAction::class)->cancel(auth()->user(), $record)),

                Action::make('vote')
                    ->label(__('votes.actions.vote'))
                    ->icon(Heroicon::OutlinedHandRaised)
                    ->visible(function (Vote $record): bool {
                        $user = auth()->user();
                        if (! $user instanceof User) {
                            return false;
                        }

                        if ($record->status !== VoteStatus::Open) {
                            return false;
                        }

                        return $record->meeting->participants()
                            ->where('user_id', $user->id)
                            ->exists();
                    })
                    ->form(function (Vote $record): array {
                        return [
                            Select::make('vote_option_id')
                                ->label(__('vote-options.model_label'))
                                ->options($record->options()->orderBy('order_column')->pluck('title', 'id')->all())
                                ->required(),
                            Textarea::make('comment')
                                ->label(__('vote-responses.fields.comment')),
                        ];
                    })
                    ->action(function (Vote $record, array $data): void {
                        app(CastVoteAction::class)->cast(auth()->user(), $record, [
                            'vote_option_id' => (int) $data['vote_option_id'],
                            'comment' => $data['comment'] ?? null,
                        ]);
                    }),

                DeleteAction::make()->label(__('actions.delete')),
                RestoreAction::make()->label(__('actions.restore')),
                ForceDeleteAction::make()->label(__('actions.force_delete')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            VoteOptionsRelationManager::class,
            VoteResponsesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVotes::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]); // reason: apenas SoftDeletingScope; incluir trashed no admin; TenantScope mantém-se no query base.

        $user = auth()->user();
        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->tenant_id === null) {
            return $query->whereRaw('1 = 0');
        }

        $query->where('tenant_id', $user->tenant_id);

        if ($user->hasRole('tenant_admin') || $user->can('manage_votes')) {
            return $query;
        }

        return $query->whereHas('meeting.participants', function (Builder $p) use ($user): void {
            $p->where('user_id', $user->id);
        });
    }
}

