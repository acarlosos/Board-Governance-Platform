<?php

namespace App\Filament\Admin\Resources\Signatures;

use App\Actions\Signatures\CancelSignatureRequestAction;
use App\Actions\Signatures\PersistSignatureRequestAction;
use App\Actions\Signatures\RejectSignatureRequestAction;
use App\Actions\Signatures\SendSignatureRequestAction;
use App\Actions\Signatures\SignSignatureRequestAction;
use App\Enums\SignatureProvider;
use App\Enums\SignatureRequestStatus;
use App\Filament\Admin\Resources\Signatures\Pages\ManageSignatureRequests;
use App\Filament\Admin\Resources\Signatures\RelationManagers\SignatureEventsRelationManager;
use App\Filament\Admin\Resources\Signatures\RelationManagers\SignatureSignersRelationManager;
use App\Models\Document;
use App\Models\Minute;
use App\Models\SignatureRequest;
use App\Models\SignatureRequestSigner;
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

class SignatureRequestResource extends Resource
{
    protected static ?string $model = SignatureRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?int $navigationSort = 90;

    public static function getNavigationGroup(): ?string
    {
        return __('signatures.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('signatures.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('signatures.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('signatures.navigation_label');
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
                $sectionLayout(Section::make(__('signatures.sections.data')))
                    ->components([
                        TextInput::make('title')
                            ->label(__('signatures.fields.title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('message')
                            ->label(__('signatures.fields.message'))
                            ->helperText(__('signatures.helper.message_sensitive'))
                            ->columnSpanFull(),
                        Select::make('provider')
                            ->label(__('signatures.fields.provider'))
                            ->required()
                            ->live()
                            ->options([
                                SignatureProvider::Internal->value => __('signatures.provider.internal'),
                                SignatureProvider::DocuSign->value => __('signatures.provider.docusign'),
                            ]),
                        Select::make('integration_id')
                            ->label(__('signatures.fields.integration'))
                            ->relationship('integration', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (callable $get): bool => ((string) $get('provider')) === SignatureProvider::DocuSign->value)
                            ->required(fn (callable $get): bool => ((string) $get('provider')) === SignatureProvider::DocuSign->value),
                        TextInput::make('status')
                            ->label(__('signatures.fields.status'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?SignatureRequest $record): bool => (bool) $record)
                            ->formatStateUsing(fn (?SignatureRequest $record): string => $record ? __('signatures.status.'.$record->status->value) : __('signatures.status.draft')),
                    ]),

                $sectionLayout(Section::make(__('signatures.sections.signable')))
                    ->components([
                        Select::make('signable_type')
                            ->label(__('signatures.fields.signable_type'))
                            ->required()
                            ->options([
                                Document::class => __('documents.model_label'),
                                Minute::class => __('minutes.model_label'),
                            ])
                            ->live(),
                        TextInput::make('signable_id')
                            ->label(__('signatures.fields.signable_id'))
                            ->numeric()
                            ->required(),
                    ]),

                $sectionLayout(Section::make(__('signatures.sections.organization')))
                    ->components([
                        Select::make('tenant_id')
                            ->label(__('fields.tenant'))
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (): bool => auth()->user()?->isSuperAdmin() === true),
                    ]),

                $sectionLayout(Section::make(__('signatures.sections.timestamps')))
                    ->columns(2)
                    ->components([
                        DateTimePicker::make('requested_at')
                            ->label(__('signatures.fields.requested_at'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?SignatureRequest $record): bool => (bool) $record),
                        DateTimePicker::make('completed_at')
                            ->label(__('signatures.fields.completed_at'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?SignatureRequest $record): bool => (bool) $record),
                        DateTimePicker::make('cancelled_at')
                            ->label(__('signatures.fields.cancelled_at'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?SignatureRequest $record): bool => (bool) $record),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('signatures.fields.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider')
                    ->label(__('signatures.fields.provider'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('signatures.provider.'.((string) $state))),
                TextColumn::make('status')
                    ->label(__('signatures.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('signatures.status.'.((string) $state))),
                TextColumn::make('signable_type')
                    ->label(__('signatures.fields.signable'))
                    ->formatStateUsing(fn ($state): string => $state === Document::class ? __('documents.model_label') : ($state === Minute::class ? __('minutes.model_label') : (string) $state))
                    ->toggleable(),
                TextColumn::make('signable_id')
                    ->label(__('signatures.fields.signable_id'))
                    ->toggleable(),
                TextColumn::make('tenant.name')
                    ->label(__('fields.tenant'))
                    ->toggleable()
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() === true),
                TextColumn::make('requester.email')
                    ->label(__('signatures.fields.requested_by'))
                    ->toggleable(),
                TextColumn::make('requested_at')
                    ->label(__('signatures.fields.requested_at'))
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('completed_at')
                    ->label(__('signatures.fields.completed_at'))
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('signatures.filters.status'))
                    ->options([
                        SignatureRequestStatus::Draft->value => __('signatures.status.draft'),
                        SignatureRequestStatus::Sent->value => __('signatures.status.sent'),
                        SignatureRequestStatus::Completed->value => __('signatures.status.completed'),
                        SignatureRequestStatus::Cancelled->value => __('signatures.status.cancelled'),
                        SignatureRequestStatus::Failed->value => __('signatures.status.failed'),
                    ]),
                TrashedFilter::make(),
            ])
            ->actions([
                Action::make('send')
                    ->label(__('signatures.actions.send'))
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->visible(fn (SignatureRequest $record): bool => $record->status === SignatureRequestStatus::Draft)
                    ->action(fn (SignatureRequest $record) => app(SendSignatureRequestAction::class)->send(auth()->user(), $record)),

                Action::make('cancel')
                    ->label(__('signatures.actions.cancel'))
                    ->icon(Heroicon::OutlinedXCircle)
                    ->visible(fn (SignatureRequest $record): bool => $record->status === SignatureRequestStatus::Sent)
                    ->action(fn (SignatureRequest $record) => app(CancelSignatureRequestAction::class)->cancel(auth()->user(), $record)),

                Action::make('sign')
                    ->label(__('signatures.actions.sign'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->visible(fn (SignatureRequest $record): bool => $record->status === SignatureRequestStatus::Sent && $record->provider === SignatureProvider::Internal)
                    ->action(function (SignatureRequest $record) {
                        $user = auth()->user();
                        $signer = $record->signers()->where('user_id', $user?->id)->firstOrFail();
                        app(SignSignatureRequestAction::class)->sign($user, $signer);
                    }),

                Action::make('reject')
                    ->label(__('signatures.actions.reject'))
                    ->icon(Heroicon::OutlinedHandRaised)
                    ->visible(fn (SignatureRequest $record): bool => $record->status === SignatureRequestStatus::Sent && $record->provider === SignatureProvider::Internal)
                    ->form([
                        Textarea::make('reason')
                            ->label(__('signatures.fields.rejection_reason'))
                            ->maxLength(180),
                    ])
                    ->action(function (SignatureRequest $record, array $data) {
                        $user = auth()->user();
                        $signer = $record->signers()->where('user_id', $user?->id)->firstOrFail();
                        app(RejectSignatureRequestAction::class)->reject($user, $signer, $data['reason'] ?? null);
                    }),

                EditAction::make()
                    ->label(__('actions.edit'))
                    ->visible(fn (SignatureRequest $record): bool => $record->status === SignatureRequestStatus::Draft)
                    ->using(fn (SignatureRequest $record, array $data): SignatureRequest => app(PersistSignatureRequestAction::class)->update(auth()->user(), $record, $data)),

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
            SignatureSignersRelationManager::class,
            SignatureEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSignatureRequests::route('/'),
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

