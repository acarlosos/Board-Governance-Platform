<?php

namespace App\Filament\Admin\Resources\Tenants;

use App\Enums\TenantStatus;
use App\Filament\Admin\Resources\Tenants\Pages\ManageTenants;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('tenants.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('tenants.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('tenants.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('tenants.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        // Ver comentário em UserResource::form — ListRecords injeta `columns(2)` no schema do modal.
        return $schema
            ->columns(1)
            ->extraAttributes([
                'class' => 'w-full min-w-0',
            ])
            ->components([
                Section::make(__('tenants.section_main'))
                    ->extraAttributes([
                        'class' => 'w-full min-w-0',
                    ])
                    ->columnSpanFull()
                    ->grow()
                    ->contained(false)
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label(__('tenants.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, mixed $state, string $operation): void {
                                if ($operation !== 'create') {
                                    return;
                                }
                                $set('slug', Str::slug((string) $state));
                            }),
                        TextInput::make('slug')
                            ->label(__('tenants.fields.slug'))
                            ->required()
                            ->maxLength(255)
                            ->alphaDash()
                            ->helperText(fn (string $operation): ?string => $operation === 'create'
                                ? __('tenants.fields.slug_helper_create')
                                : __('tenants.fields.slug_helper_edit'))
                            ->rules(fn (?Model $record): array => filled($record?->getKey())
                                ? []
                                : [Rule::unique('tenants', 'slug')])
                            ->disabled(fn (?Model $record): bool => filled($record?->getKey()))
                            ->dehydrated(fn (?Model $record): bool => ! filled($record?->getKey())),
                        TextInput::make('document')
                            ->label(__('tenants.fields.document'))
                            ->maxLength(255),
                        Select::make('status')
                            ->label(__('tenants.fields.status'))
                            ->options(self::tenantStatusOptions())
                            ->required()
                            ->native(false),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function tenantStatusOptions(): array
    {
        $out = [];
        foreach (TenantStatus::cases() as $case) {
            $out[$case->value] = __('tenants.status.'.$case->value);
        }

        return $out;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('tenants.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('tenants.fields.slug'))
                    ->searchable(),
                TextColumn::make('document')
                    ->label(__('tenants.fields.document'))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('tenants.fields.status'))
                    ->formatStateUsing(fn (TenantStatus $state): string => __('tenants.status.'.$state->value))
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('tenants.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('tenants.filters.status'))
                    ->options(self::tenantStatusOptions()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('actions.edit'))
                    ->modalWidth(Width::FiveExtraLarge),
                DeleteAction::make()
                    ->label(__('actions.delete')),
                RestoreAction::make()
                    ->label(__('actions.restore')),
                ForceDeleteAction::make()
                    ->label(__('actions.force_delete')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('actions.delete_bulk')),
                    RestoreBulkAction::make()
                        ->label(__('actions.restore_bulk')),
                    ForceDeleteBulkAction::make()
                        ->label(__('actions.force_delete_bulk')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTenants::route('/'),
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
