<?php

namespace App\Filament\Admin\Resources\Minutes;

use App\Actions\Minutes\ApproveMinuteAction;
use App\Actions\Minutes\ArchiveMinuteAction;
use App\Actions\Minutes\PersistMinuteAction;
use App\Actions\Minutes\RejectMinuteAction;
use App\Actions\Minutes\ReopenRejectedMinuteAction;
use App\Actions\Minutes\SubmitMinuteForReviewAction;
use App\Enums\MinuteStatus;
use App\Filament\Admin\Resources\Minutes\Pages\ManageMinutes;
use App\Filament\Admin\Resources\Minutes\RelationManagers\MinuteApprovalsRelationManager;
use App\Filament\Admin\Resources\Minutes\RelationManagers\MinuteVersionsRelationManager;
use App\Models\Minute;
use App\Models\User;
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
use Filament\Forms\Components\RichEditor;
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

class MinuteResource extends Resource
{
    protected static ?string $model = Minute::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): ?string
    {
        return __('minutes.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('minutes.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('minutes.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('minutes.navigation_label');
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
                $sectionLayout(Section::make(__('minutes.sections.data')))
                    ->components([
                        TextInput::make('title')
                            ->label(__('minutes.fields.title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('meeting_id')
                            ->label(__('minutes.fields.meeting'))
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

                $sectionLayout(Section::make(__('minutes.sections.content')))
                    ->columns(1)
                    ->components([
                        RichEditor::make('content')
                            ->label(__('minutes.fields.content'))
                            ->required()
                            ->disabled(fn (?Minute $record): bool => $record?->status !== MinuteStatus::Draft)
                            ->columnSpanFull(),
                        Textarea::make('status')
                            ->label(__('minutes.fields.status'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?Minute $record): bool => (bool) $record)
                            ->formatStateUsing(fn (?Minute $record): string => $record ? __('minutes.status.'.$record->status->value) : __('minutes.status.draft')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('minutes.fields.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('meeting.title')
                    ->label(__('minutes.fields.meeting'))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('minutes.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('minutes.status.'.((string) $state))),
                TextColumn::make('current_version_id')
                    ->label(__('minutes.fields.current_version'))
                    ->toggleable()
                    ->formatStateUsing(fn ($state): string => $state ? (string) $state : '-'),
                TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('minutes.filters.status'))
                    ->options([
                        MinuteStatus::Draft->value => __('minutes.status.draft'),
                        MinuteStatus::InReview->value => __('minutes.status.in_review'),
                        MinuteStatus::Approved->value => __('minutes.status.approved'),
                        MinuteStatus::Rejected->value => __('minutes.status.rejected'),
                        MinuteStatus::Archived->value => __('minutes.status.archived'),
                    ]),
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->label(__('actions.edit'))
                    ->visible(fn (Minute $record): bool => $record->status === MinuteStatus::Draft)
                    ->using(function (Minute $record, array $data): Minute {
                        // edição só draft (enforced também na Action)
                        $data['status'] = MinuteStatus::Draft->value;
                        return app(PersistMinuteAction::class)->update(auth()->user(), $record, $data);
                    }),

                Action::make('submit_for_review')
                    ->label(__('minutes.actions.submit_for_review'))
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->visible(fn (Minute $record): bool => $record->status === MinuteStatus::Draft)
                    ->requiresConfirmation()
                    ->action(fn (Minute $record) => app(SubmitMinuteForReviewAction::class)->submit(auth()->user(), $record)),

                Action::make('approve')
                    ->label(__('minutes.actions.approve'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->visible(fn (Minute $record): bool => $record->status === MinuteStatus::InReview)
                    ->action(fn (Minute $record) => app(ApproveMinuteAction::class)->approve(auth()->user(), $record)),

                Action::make('reject')
                    ->label(__('minutes.actions.reject'))
                    ->icon(Heroicon::OutlinedXCircle)
                    ->visible(fn (Minute $record): bool => $record->status === MinuteStatus::InReview)
                    ->action(fn (Minute $record) => app(RejectMinuteAction::class)->reject(auth()->user(), $record)),

                Action::make('reopen')
                    ->label(__('minutes.actions.reopen'))
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (Minute $record): bool => $record->status === MinuteStatus::Rejected)
                    ->requiresConfirmation()
                    ->action(fn (Minute $record) => app(ReopenRejectedMinuteAction::class)->reopen(auth()->user(), $record)),

                Action::make('archive')
                    ->label(__('minutes.actions.archive'))
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->visible(fn (Minute $record): bool => $record->status !== MinuteStatus::Archived)
                    ->requiresConfirmation()
                    ->action(fn (Minute $record) => app(ArchiveMinuteAction::class)->archive(auth()->user(), $record)),

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
            MinuteVersionsRelationManager::class,
            MinuteApprovalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMinutes::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);

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

        if ($user->hasRole('tenant_admin') || $user->can('manage_minutes')) {
            return $query;
        }

        // Participante: vê apenas atas das reuniões onde participa.
        return $query->whereHas('meeting.participants', function (Builder $p) use ($user): void {
            $p->where('user_id', $user->id);
        });
    }
}

