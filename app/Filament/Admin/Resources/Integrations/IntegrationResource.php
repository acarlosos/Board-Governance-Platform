<?php

namespace App\Filament\Admin\Resources\Integrations;

use App\Actions\Integrations\DisableIntegrationAction;
use App\Actions\Integrations\EnableIntegrationAction;
use App\Actions\Integrations\PersistIntegrationAction;
use App\Actions\Integrations\TestIntegrationAction;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Filament\Admin\Resources\Integrations\Pages\ManageIntegrations;
use App\Filament\Admin\Resources\Integrations\RelationManagers\IntegrationLogsRelationManager;
use App\Integrations\IntegrationConfigSchemaRegistry;
use App\Models\Integration;
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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
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

class IntegrationResource extends Resource
{
    protected static ?string $model = Integration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static ?int $navigationSort = 80;

    public static function getNavigationGroup(): ?string
    {
        return __('integrations.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('integrations.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('integrations.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('integrations.navigation_label');
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
                $sectionLayout(Section::make(__('integrations.sections.data')))
                    ->components([
                        TextInput::make('name')
                            ->label(__('integrations.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('type')
                            ->label(__('integrations.fields.type'))
                            ->required()
                            ->options([
                                IntegrationType::Email->value => __('integrations.type.email'),
                                IntegrationType::Storage->value => __('integrations.type.storage'),
                                IntegrationType::Signature->value => __('integrations.type.signature'),
                                IntegrationType::VideoConference->value => __('integrations.type.video_conference'),
                                IntegrationType::Reporting->value => __('integrations.type.reporting'),
                                IntegrationType::Identity->value => __('integrations.type.identity'),
                            ]),
                        Select::make('provider')
                            ->label(__('integrations.fields.provider'))
                            ->required()
                            ->live()
                            ->options([
                                IntegrationProvider::Smtp->value => __('integrations.provider.smtp'),
                                IntegrationProvider::Microsoft365->value => __('integrations.provider.microsoft_365'),
                                IntegrationProvider::OneDrive->value => __('integrations.provider.onedrive'),
                                IntegrationProvider::DocuSign->value => __('integrations.provider.docusign'),
                                IntegrationProvider::Teams->value => __('integrations.provider.teams'),
                                IntegrationProvider::Zoom->value => __('integrations.provider.zoom'),
                                IntegrationProvider::LookerStudio->value => __('integrations.provider.looker_studio'),
                            ]),
                        TextInput::make('status')
                            ->label(__('integrations.fields.status'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?Integration $record): bool => (bool) $record)
                            ->formatStateUsing(fn (?Integration $record): string => $record ? __('integrations.status.'.$record->status->value) : __('integrations.status.inactive')),
                    ]),

                $sectionLayout(Section::make(__('integrations.sections.config')))
                    ->columns(2)
                    ->components([
                        TextInput::make('config.host')
                            ->label(__('integrations.config.host'))
                            ->visible(fn (callable $get): bool => ((string) $get('provider')) === IntegrationProvider::Smtp->value),
                        TextInput::make('config.port')
                            ->label(__('integrations.config.port'))
                            ->numeric()
                            ->visible(fn (callable $get): bool => ((string) $get('provider')) === IntegrationProvider::Smtp->value),
                        TextInput::make('config.username')
                            ->label(__('integrations.config.username'))
                            ->visible(fn (callable $get): bool => ((string) $get('provider')) === IntegrationProvider::Smtp->value),
                        TextInput::make('config.password')
                            ->label(__('integrations.config.password'))
                            ->password()
                            ->revealable()
                            ->helperText(__('integrations.helper.keep_secret_if_empty'))
                            ->visible(fn (callable $get): bool => ((string) $get('provider')) === IntegrationProvider::Smtp->value),
                        TextInput::make('config.encryption')
                            ->label(__('integrations.config.encryption'))
                            ->visible(fn (callable $get): bool => ((string) $get('provider')) === IntegrationProvider::Smtp->value),
                        TextInput::make('config.from_address')
                            ->label(__('integrations.config.from_address'))
                            ->visible(fn (callable $get): bool => ((string) $get('provider')) === IntegrationProvider::Smtp->value),
                        TextInput::make('config.from_name')
                            ->label(__('integrations.config.from_name'))
                            ->visible(fn (callable $get): bool => ((string) $get('provider')) === IntegrationProvider::Smtp->value),

                        TextInput::make('config.tenant_id')
                            ->label(__('integrations.config.tenant_id'))
                            ->visible(fn (callable $get): bool => in_array((string) $get('provider'), [
                                IntegrationProvider::Microsoft365->value,
                                IntegrationProvider::OneDrive->value,
                                IntegrationProvider::Teams->value,
                            ], true)),
                        TextInput::make('config.client_id')
                            ->label(__('integrations.config.client_id'))
                            ->visible(fn (callable $get): bool => in_array((string) $get('provider'), [
                                IntegrationProvider::Microsoft365->value,
                                IntegrationProvider::OneDrive->value,
                                IntegrationProvider::Teams->value,
                                IntegrationProvider::Zoom->value,
                            ], true)),
                        TextInput::make('config.client_secret')
                            ->label(__('integrations.config.client_secret'))
                            ->password()
                            ->revealable()
                            ->helperText(__('integrations.helper.keep_secret_if_empty'))
                            ->visible(fn (callable $get): bool => in_array((string) $get('provider'), [
                                IntegrationProvider::Microsoft365->value,
                                IntegrationProvider::OneDrive->value,
                                IntegrationProvider::Teams->value,
                                IntegrationProvider::Zoom->value,
                            ], true)),
                        TextInput::make('config.redirect_uri')
                            ->label(__('integrations.config.redirect_uri'))
                            ->visible(fn (callable $get): bool => ((string) $get('provider')) === IntegrationProvider::Microsoft365->value),
                        TextInput::make('config.root_folder')
                            ->label(__('integrations.config.root_folder'))
                            ->visible(fn (callable $get): bool => ((string) $get('provider')) === IntegrationProvider::OneDrive->value),

                        TextInput::make('config.account_id')
                            ->label(__('integrations.config.account_id'))
                            ->visible(fn (callable $get): bool => in_array((string) $get('provider'), [
                                IntegrationProvider::DocuSign->value,
                                IntegrationProvider::Zoom->value,
                            ], true)),
                        TextInput::make('config.integration_key')
                            ->label(__('integrations.config.integration_key'))
                            ->visible(fn (callable $get): bool => ((string) $get('provider')) === IntegrationProvider::DocuSign->value),
                        TextInput::make('config.user_id')
                            ->label(__('integrations.config.user_id'))
                            ->visible(fn (callable $get): bool => ((string) $get('provider')) === IntegrationProvider::DocuSign->value),
                        TextInput::make('config.private_key')
                            ->label(__('integrations.config.private_key'))
                            ->password()
                            ->revealable()
                            ->helperText(__('integrations.helper.keep_secret_if_empty'))
                            ->visible(fn (callable $get): bool => ((string) $get('provider')) === IntegrationProvider::DocuSign->value),
                        TextInput::make('config.base_uri')
                            ->label(__('integrations.config.base_uri'))
                            ->visible(fn (callable $get): bool => ((string) $get('provider')) === IntegrationProvider::DocuSign->value),

                        TextInput::make('config.report_url')
                            ->label(__('integrations.config.report_url'))
                            ->url()
                            ->visible(fn (callable $get): bool => ((string) $get('provider')) === IntegrationProvider::LookerStudio->value),
                    ]),

                $sectionLayout(Section::make(__('integrations.sections.test')))
                    ->columns(2)
                    ->components([
                        DateTimePicker::make('last_tested_at')
                            ->label(__('integrations.fields.last_tested_at'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?Integration $record): bool => (bool) $record),
                        TextInput::make('last_test_status')
                            ->label(__('integrations.fields.last_test_status'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?Integration $record): bool => (bool) $record),
                        TextInput::make('last_test_message')
                            ->label(__('integrations.fields.last_test_message'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?Integration $record): bool => (bool) $record)
                            ->columnSpanFull(),
                    ]),

                $sectionLayout(Section::make(__('integrations.sections.organization')))
                    ->components([
                        Select::make('tenant_id')
                            ->label(__('fields.tenant'))
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (): bool => auth()->user()?->isSuperAdmin() === true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('integrations.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('integrations.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('integrations.type.'.((string) $state))),
                TextColumn::make('provider')
                    ->label(__('integrations.fields.provider'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('integrations.provider.'.((string) $state))),
                TextColumn::make('status')
                    ->label(__('integrations.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('integrations.status.'.((string) $state))),
                TextColumn::make('tenant.name')
                    ->label(__('fields.tenant'))
                    ->toggleable()
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() === true),
                TextColumn::make('last_test_status')
                    ->label(__('integrations.fields.last_test_status'))
                    ->toggleable(),
                TextColumn::make('last_tested_at')
                    ->label(__('integrations.fields.last_tested_at'))
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('integrations.filters.status'))
                    ->options([
                        IntegrationStatus::Inactive->value => __('integrations.status.inactive'),
                        IntegrationStatus::Active->value => __('integrations.status.active'),
                        IntegrationStatus::Error->value => __('integrations.status.error'),
                    ]),
                TrashedFilter::make(),
            ])
            ->actions([
                Action::make('test')
                    ->label(__('integrations.actions.test'))
                    ->icon(Heroicon::OutlinedBeaker)
                    ->action(fn (Integration $record) => app(TestIntegrationAction::class)->test(auth()->user(), $record)),

                Action::make('enable')
                    ->label(__('integrations.actions.enable'))
                    ->icon(Heroicon::OutlinedBolt)
                    ->visible(fn (Integration $record): bool => $record->status !== IntegrationStatus::Active)
                    ->action(fn (Integration $record) => app(EnableIntegrationAction::class)->enable(auth()->user(), $record)),

                Action::make('disable')
                    ->label(__('integrations.actions.disable'))
                    ->icon(Heroicon::OutlinedPauseCircle)
                    ->visible(fn (Integration $record): bool => $record->status === IntegrationStatus::Active)
                    ->action(fn (Integration $record) => app(DisableIntegrationAction::class)->disable(auth()->user(), $record)),

                EditAction::make()
                    ->label(__('actions.edit'))
                    ->using(fn (Integration $record, array $data): Integration => app(PersistIntegrationAction::class)->update(auth()->user(), $record, $data)),
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
            IntegrationLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageIntegrations::route('/'),
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

        return $query->where('tenant_id', $user->tenant_id);
    }
}

