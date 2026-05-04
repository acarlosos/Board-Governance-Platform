<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('fields.user.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('fields.user.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('fields.user.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        $localeOptions = collect(config('localization.supported', []))
            ->mapWithKeys(fn (string $locale): array => [
                $locale => config('localization.labels.'.$locale, $locale),
            ])
            ->all();

        return $schema
            ->components([
                Section::make(__('fields.user.section_profile'))
                    ->components([
                        TextInput::make('name')
                            ->label(__('fields.user.name'))
                            ->required(),
                        TextInput::make('email')
                            ->label(__('fields.user.email'))
                            ->email()
                            ->required(),
                        Select::make('locale')
                            ->label(__('fields.user.locale'))
                            ->options($localeOptions)
                            ->default('pt_BR')
                            ->required(),
                        DateTimePicker::make('email_verified_at')
                            ->label(__('fields.user.email_verified_at')),
                        TextInput::make('password')
                            ->label(__('fields.user.password'))
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('fields.user.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('fields.user.email'))
                    ->searchable(),
                TextColumn::make('locale')
                    ->label(__('fields.user.locale'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('email_verified_at')
                    ->label(__('fields.user.email_verified_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
