<?php

namespace App\Filament\Admin\Resources\Documents;

use App\Actions\Documents\ArchiveDocumentAction;
use App\Actions\Documents\RecordDocumentAccessAction;
use App\Actions\Documents\PersistDocumentAction;
use App\Actions\Documents\PublishDocumentAction;
use App\Enums\DocumentAccessAction;
use App\Enums\DocumentStatus;
use App\Filament\Admin\Resources\Documents\Pages\ManageDocuments;
use App\Filament\Admin\Resources\Documents\RelationManagers\DocumentAccessLogsRelationManager;
use App\Filament\Admin\Resources\Documents\RelationManagers\DocumentVersionsRelationManager;
use App\Models\Document;
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
use Filament\Forms\Components\FileUpload;
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
use Illuminate\Support\Facades\Storage;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 40;

    public static function getNavigationGroup(): ?string
    {
        return __('documents.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('documents.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('documents.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('documents.navigation_label');
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
                $sectionLayout(Section::make(__('documents.sections.main')))
                    ->components([
                        TextInput::make('title')
                            ->label(__('documents.fields.title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label(__('documents.fields.description'))
                            ->columnSpanFull(),
                        TextInput::make('category')
                            ->label(__('documents.fields.category'))
                            ->maxLength(255),
                        Select::make('status')
                            ->label(__('documents.fields.status'))
                            ->required()
                            ->options([
                                DocumentStatus::Draft->value => __('documents.statuses.draft'),
                                DocumentStatus::Published->value => __('documents.statuses.published'),
                                DocumentStatus::Archived->value => __('documents.statuses.archived'),
                            ]),
                        FileUpload::make('initial_file')
                            ->label(__('documents.fields.initial_file'))
                            ->helperText(__('documents.helpers.initial_file'))
                            ->storeFiles(false)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->columnSpanFull(),
                    ]),

                $sectionLayout(Section::make(__('documents.sections.context')))
                    ->components([
                        Select::make('board_id')
                            ->label(__('documents.fields.board'))
                            ->relationship(
                                name: 'board',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query): Builder {
                                    $user = auth()->user();
                                    if (! $user instanceof User || $user->isSuperAdmin()) {
                                        return $query;
                                    }

                                    return $query->where('tenant_id', $user->tenant_id);
                                },
                            )
                            ->searchable()
                            ->preload(),
                        Select::make('meeting_id')
                            ->label(__('documents.fields.meeting'))
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
                            ->preload(),
                    ]),

                $sectionLayout(Section::make(__('documents.sections.organization')))
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() === true)
                    ->components([
                        TextInput::make('tenant_id')
                            ->label(__('fields.tenant'))
                            ->numeric()
                            ->required()
                            ->helperText(__('documents.helpers.tenant_only_super_admin')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('documents.fields.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('documents.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => match ((string) $state) {
                        DocumentStatus::Draft->value => __('documents.statuses.draft'),
                        DocumentStatus::Published->value => __('documents.statuses.published'),
                        DocumentStatus::Archived->value => __('documents.statuses.archived'),
                        default => (string) $state,
                    }),
                TextColumn::make('board.name')
                    ->label(__('documents.fields.board'))
                    ->toggleable(),
                TextColumn::make('meeting.title')
                    ->label(__('documents.fields.meeting'))
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('fields.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('documents.filters.status'))
                    ->options([
                        DocumentStatus::Draft->value => __('documents.statuses.draft'),
                        DocumentStatus::Published->value => __('documents.statuses.published'),
                        DocumentStatus::Archived->value => __('documents.statuses.archived'),
                    ]),
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->label(__('actions.edit'))
                    ->using(function (Document $record, array $data): Document {
                        return app(PersistDocumentAction::class)->update(auth()->user(), $record, $data);
                    })
                    ->after(function (Document $record): void {
                        app(RecordDocumentAccessAction::class)->record(
                            actor: auth()->user(),
                            document: $record,
                            action: DocumentAccessAction::Viewed,
                            version: $record->currentVersion,
                        );
                    }),

                Action::make('download_current')
                    ->label(__('documents.actions.download_current'))
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->visible(fn (Document $record): bool => (bool) $record->current_version_id)
                    ->action(function (Document $record) {
                        $version = $record->currentVersion;
                        if (! $version) {
                            return null;
                        }

                        app(RecordDocumentAccessAction::class)->record(
                            actor: auth()->user(),
                            document: $record,
                            action: DocumentAccessAction::Downloaded,
                            version: $version,
                        );

                        return response()->download(
                            Storage::disk($version->disk)->path($version->file_path),
                            $version->original_name,
                        );
                    }),

                Action::make('publish')
                    ->label(__('documents.actions.publish'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->visible(fn (Document $record): bool => $record->status === DocumentStatus::Draft)
                    ->requiresConfirmation()
                    ->action(fn (Document $record) => app(PublishDocumentAction::class)->publish(auth()->user(), $record)),

                Action::make('archive')
                    ->label(__('documents.actions.archive'))
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->visible(fn (Document $record): bool => $record->status !== DocumentStatus::Archived)
                    ->requiresConfirmation()
                    ->action(fn (Document $record) => app(ArchiveDocumentAction::class)->archive(auth()->user(), $record)),

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
            DocumentVersionsRelationManager::class,
            DocumentAccessLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDocuments::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

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

        if ($user->hasRole('tenant_admin') || $user->can('manage_documents')) {
            return $query;
        }

        // board_member: docs do board onde é membro ativo (direto ou via meeting->board)
        if ($user->hasRole('board_member')) {
            return $query->where(function (Builder $q) use ($user): void {
                $q->whereHas('board.boardMembers', function (Builder $bm) use ($user): void {
                    $bm->where('user_id', $user->id)->where('status', 'active');
                })->orWhereHas('meeting.board.boardMembers', function (Builder $bm) use ($user): void {
                    $bm->where('user_id', $user->id)->where('status', 'active');
                })->orWhereHas('meeting.participants', function (Builder $p) use ($user): void {
                    $p->where('user_id', $user->id);
                });
            });
        }

        // participante: apenas docs de reuniões onde participa
        return $query->whereHas('meeting.participants', function (Builder $p) use ($user): void {
            $p->where('user_id', $user->id);
        });
    }
}

