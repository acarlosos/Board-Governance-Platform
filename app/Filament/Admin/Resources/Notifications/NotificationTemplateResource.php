<?php

namespace App\Filament\Admin\Resources\Notifications;

use App\Actions\Notifications\PersistNotificationTemplateAction;
use App\Enums\NotificationChannel;
use App\Enums\NotificationTemplateStatus;
use App\Filament\Admin\Resources\Notifications\Pages\ManageNotificationTemplates;
use App\Models\NotificationTemplate;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
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

class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 95;

    public static function getNavigationGroup(): ?string
    {
        return __('notification-templates.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('notification-templates.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('notification-templates.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('notification-templates.navigation_label');
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
                $sectionLayout(Section::make(__('notification-templates.sections.data')))
                    ->components([
                        TextInput::make('key')
                            ->label(__('notification-templates.fields.key'))
                            ->required()
                            ->maxLength(128),
                        TextInput::make('name')
                            ->label(__('notification-templates.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Select::make('locale')
                            ->label(__('notification-templates.fields.locale'))
                            ->required()
                            ->options([
                                'pt_BR' => 'pt_BR',
                                'en' => 'en',
                                'es' => 'es',
                            ]),
                        Select::make('channel')
                            ->label(__('notification-templates.fields.channel'))
                            ->required()
                            ->options([
                                NotificationChannel::Database->value => __('notifications.channel.database'),
                                NotificationChannel::Email->value => __('notifications.channel.email'),
                            ]),
                        Select::make('status')
                            ->label(__('notification-templates.fields.status'))
                            ->required()
                            ->options([
                                NotificationTemplateStatus::Active->value => __('notification-templates.status.active'),
                                NotificationTemplateStatus::Inactive->value => __('notification-templates.status.inactive'),
                            ]),
                    ]),

                $sectionLayout(Section::make(__('notification-templates.sections.content')))
                    ->columns(1)
                    ->components([
                        TextInput::make('subject')
                            ->label(__('notification-templates.fields.subject'))
                            ->maxLength(255),
                        RichEditor::make('body')
                            ->label(__('notification-templates.fields.body'))
                            ->required(),
                    ]),

                $sectionLayout(Section::make(__('notification-templates.sections.variables')))
                    ->columns(1)
                    ->components([
                        KeyValue::make('variables')
                            ->label(__('notification-templates.fields.variables'))
                            ->keyLabel(__('notification-templates.fields.variable_key'))
                            ->valueLabel(__('notification-templates.fields.variable_description')),
                    ]),

                $sectionLayout(Section::make(__('notification-templates.sections.organization')))
                    ->components([
                        Select::make('tenant_id')
                            ->label(__('notification-templates.fields.tenant'))
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (): bool => auth()->user()?->isSuperAdmin() === true)
                            ->helperText(__('notification-templates.helper.global_or_tenant')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label(__('notification-templates.fields.key'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('notification-templates.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('locale')
                    ->label(__('notification-templates.fields.locale'))
                    ->badge(),
                TextColumn::make('channel')
                    ->label(__('notification-templates.fields.channel'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('notifications.channel.'.((string) $state))),
                TextColumn::make('status')
                    ->label(__('notification-templates.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('notification-templates.status.'.((string) $state))),
                TextColumn::make('tenant.name')
                    ->label(__('notification-templates.fields.tenant'))
                    ->toggleable()
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() === true),
                TextColumn::make('updated_at')
                    ->label(__('notification-templates.fields.updated_at'))
                    ->dateTime()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('notification-templates.filters.status'))
                    ->options([
                        NotificationTemplateStatus::Active->value => __('notification-templates.status.active'),
                        NotificationTemplateStatus::Inactive->value => __('notification-templates.status.inactive'),
                    ]),
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->label(__('actions.edit'))
                    ->using(fn (NotificationTemplate $record, array $data) => app(PersistNotificationTemplateAction::class)->update(auth()->user(), $record, $data)),
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

    public static function getPages(): array
    {
        return [
            'index' => ManageNotificationTemplates::route('/'),
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

        // tenant vê os próprios + globais (fallback), mas Actions/Policy bloqueiam edição do global
        return $query->where(function (Builder $q) use ($user): void {
            $q->whereNull('tenant_id')->orWhere('tenant_id', $user->tenant_id);
        });
    }
}

