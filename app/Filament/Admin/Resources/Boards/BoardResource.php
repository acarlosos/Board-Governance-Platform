<?php

namespace App\Filament\Admin\Resources\Boards;

use App\Actions\Boards\ArchiveBoardAction;
use App\Actions\Boards\PersistBoardAction;
use App\Enums\BoardStatus;
use App\Filament\Admin\Resources\Boards\Pages\ManageBoards;
use App\Filament\Admin\Resources\Boards\RelationManagers\BoardMembersRelationManager;
use App\Models\Board;
use App\Support\Filament\RelationManagerModalAction;
use App\Support\Filament\RemapValidationToMountedAction;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BoardResource extends Resource
{
    protected static ?string $model = Board::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('boards.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('boards.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('boards.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('boards.navigation_label');
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
                $sectionLayout(Section::make(__('boards.section_main')))
                    ->components([
                        TextInput::make('name')
                            ->label(__('boards.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label(__('boards.fields.description'))
                            ->rows(4)
                            ->columnSpanFull(),
                        Select::make('status')
                            ->label(__('boards.fields.status'))
                            ->options(self::boardStatusOptions())
                            ->default(BoardStatus::Active->value)
                            ->required()
                            ->native(false),
                    ]),
                $sectionLayout(Section::make(__('boards.section_organization')))
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                    ->components([
                        Select::make('tenant_id')
                            ->label(__('boards.fields.tenant'))
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
    private static function boardStatusOptions(): array
    {
        $out = [];
        foreach (BoardStatus::cases() as $case) {
            $out[$case->value] = __('boards.status.'.$case->value);
        }

        return $out;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('boards.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->label(__('boards.fields.tenant'))
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('boards.fields.status'))
                    ->formatStateUsing(fn (BoardStatus $state): string => __('boards.status.'.$state->value))
                    ->badge()
                    ->sortable(),
                TextColumn::make('active_members_count')
                    ->label(__('boards.fields.active_members'))
                    ->getStateUsing(fn (Board $record): int => $record->boardMembers()->where('status', 'active')->count())
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->withCount(['boardMembers as active_members_count' => fn (Builder $q) => $q->where('status', 'active')])->orderBy('active_members_count', $direction)),
                TextColumn::make('created_at')
                    ->label(__('boards.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tenant_id')
                    ->label(__('boards.filters.tenant'))
                    ->relationship('tenant', 'name')
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                SelectFilter::make('status')
                    ->label(__('boards.filters.status'))
                    ->options(self::boardStatusOptions()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('actions.edit'))
                    ->modalWidth(Width::FiveExtraLarge)
                    ->using(function (array $data, HasActions&HasSchemas $livewire, Model $record): void {
                        /** @var Board $record */
                        RemapValidationToMountedAction::run(
                            fn () => app(PersistBoardAction::class)->update(auth()->user(), $record, $data),
                            $livewire,
                        );
                    }),
                RelationManagerModalAction::make(
                    name: 'members',
                    label: __('board-members.section_main'),
                    relationManager: BoardMembersRelationManager::class,
                    icon: Heroicon::OutlinedUserGroup,
                    pageClass: ManageBoards::class,
                    visible: fn (Board $record): bool => auth()->user()?->can('update', $record) ?? false,
                    recordTitle: fn (Board $record): string => $record->name,
                ),
                Action::make('archive')
                    ->label(__('boards.actions.archive'))
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->visible(fn (Board $record): bool => $record->status !== BoardStatus::Archived)
                    ->requiresConfirmation()
                    ->action(fn (Board $record) => app(ArchiveBoardAction::class)->archive(auth()->user(), $record)),
                DeleteAction::make()
                    ->label(__('actions.delete')),
                RestoreAction::make()
                    ->label(__('actions.restore')),
                ForceDeleteAction::make()
                    ->label(__('actions.force_delete')),
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

        // tenant-safe via TenantScope (BelongsToTenant). Para board_member, restringir só aos boards onde participa.
        if (! $user->hasRole('tenant_admin') && ! $user->can('manage_boards')) {
            $query->whereHas('boardMembers', function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id)->where('status', 'active');
            });
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            BoardMembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBoards::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]); // reason: apenas SoftDeletingScope; resolver URL admin a registos soft-deleted; TenantScope mantém-se no query base.
    }
}
