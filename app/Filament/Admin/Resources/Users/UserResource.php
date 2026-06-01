<?php

namespace App\Filament\Admin\Resources\Users;

use App\Actions\Filament\PersistPanelUserAction;
use App\Enums\UserStatus;
use App\Filament\Admin\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Services\Security\PasswordPolicyService;
use App\Support\Filament\RemapValidationToMountedAction;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('users.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('users.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('users.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('users.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        $localeOptions = collect(config('localization.supported', []))
            ->mapWithKeys(fn (string $locale): array => [
                $locale => config('localization.labels.'.$locale, $locale),
            ])
            ->all();

        $statusOptions = collect(UserStatus::cases())
            ->mapWithKeys(fn (UserStatus $case): array => [
                $case->value => __('users.status.'.$case->value),
            ])
            ->all();

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
                $sectionLayout(Section::make(__('users.section_account')))
                    ->components([
                        TextInput::make('name')
                            ->label(__('users.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('users.fields.email'))
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('password')
                            ->label(__('users.fields.password'))
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->rules(fn (string $operation): array => $operation === 'create'
                                ? array_merge(['required'], app(PasswordPolicyService::class)->rules())
                                : array_merge(['sometimes'], app(PasswordPolicyService::class)->rules()))
                            ->validationAttribute(__('users.validation.attributes.password'))
                            ->helperText(fn (string $operation): ?string => match ($operation) {
                                'create' => __('users.fields.password_helper_create'),
                                'edit' => __('users.fields.password_helper_edit'),
                                default => null,
                            })
                            ->columnSpanFull(),
                    ]),
                $sectionLayout(Section::make(__('users.section_organization')))
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                    ->components([
                        Select::make('tenant_id')
                            ->label(__('users.fields.tenant'))
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get, string $operation): bool => $operation === 'create'
                                && ! (bool) $get('is_super_admin'))
                            ->helperText(__('users.fields.tenant_helper'))
                            ->columnSpanFull(),
                    ]),
                $sectionLayout(Section::make(__('users.section_permissions')))
                    ->components([
                        CheckboxList::make('roles')
                            ->label(__('users.fields.roles'))
                            ->options(fn (): array => self::roleOptionsForForm())
                            ->columns(2)
                            ->required(fn (Get $get): bool => ! (bool) $get('is_super_admin'))
                            ->helperText(__('users.fields.roles_helper'))
                            ->columnSpanFull(),
                        Toggle::make('is_super_admin')
                            ->label(__('users.fields.is_super_admin'))
                            ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                            ->dehydrated(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                            ->columnSpanFull(),
                    ]),
                $sectionLayout(Section::make(__('users.section_preferences')))
                    ->components([
                        Select::make('locale')
                            ->label(__('users.fields.locale'))
                            ->options($localeOptions)
                            ->default('pt_BR')
                            ->required(),
                        Select::make('status')
                            ->label(__('users.fields.status'))
                            ->options($statusOptions)
                            ->required()
                            ->native(false),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function roleOptionsForForm(): array
    {
        if (auth()->user()?->isSuperAdmin()) {
            return Role::query()
                ->where('guard_name', 'web')
                ->where('name', '!=', PersistPanelUserAction::ROLE_SUPER_ADMIN)
                ->orderBy('name')
                ->pluck('name', 'name')
                ->mapWithKeys(fn (string $name): array => [$name => __('roles.'.$name)])
                ->all();
        }

        return collect(PersistPanelUserAction::ROLES_ASSIGNABLE_BY_TENANT_ADMIN)
            ->mapWithKeys(fn (string $name): array => [$name => __('roles.'.$name)])
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('users.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('users.fields.email'))
                    ->searchable(),
                TextColumn::make('tenant.name')
                    ->label(__('users.fields.tenant'))
                    ->searchable()
                    ->sortable()
                    ->placeholder(__('users.fields.tenant_placeholder')),
                TextColumn::make('assigned_roles')
                    ->label(__('users.fields.roles'))
                    ->getStateUsing(fn (User $record): string => $record->getRoleNames()->implode(', '))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('users.fields.status'))
                    ->formatStateUsing(fn (UserStatus $state): string => __('users.status.'.$state->value))
                    ->badge()
                    ->color(fn (UserStatus $state): string => match ($state) {
                        UserStatus::Active => 'success',
                        UserStatus::Inactive => 'gray',
                        UserStatus::Suspended => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('locale')
                    ->label(__('users.fields.locale'))
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('users.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tenant_id')
                    ->label(__('users.filters.tenant'))
                    ->relationship('tenant', 'name')
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                SelectFilter::make('status')
                    ->label(__('users.filters.status'))
                    ->options(collect(UserStatus::cases())->mapWithKeys(fn (UserStatus $c) => [$c->value => __('users.status.'.$c->value)])->all()),
                SelectFilter::make('spatie_role')
                    ->label(__('users.filters.role'))
                    ->options(fn (): array => Role::query()
                        ->where('guard_name', 'web')
                        ->where('name', '!=', PersistPanelUserAction::ROLE_SUPER_ADMIN)
                        ->orderBy('name')
                        ->pluck('name', 'name')
                        ->all())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = data_get($data, 'value') ?? data_get($data, 'values.value');

                        if (! filled($value)) {
                            return $query;
                        }

                        return $query->role($value);
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('actions.edit'))
                    ->modalWidth(Width::FiveExtraLarge)
                    ->fillForm(function (Model $record): array {
                        /** @var User $record */
                        return [
                            ...$record->attributesToArray(),
                            'password' => null,
                            'roles' => $record->getRoleNames()
                                ->reject(fn (string $r): bool => $r === PersistPanelUserAction::ROLE_SUPER_ADMIN)
                                ->values()
                                ->all(),
                        ];
                    })
                    ->using(function (array $data, HasActions&HasSchemas $livewire, Model $record): void {
                        RemapValidationToMountedAction::run(
                            fn () => app(PersistPanelUserAction::class)->update(auth()->user(), $record, $data),
                            $livewire,
                        );
                    }),
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();
        if ($user && ! $user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
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
