<?php

namespace App\Filament\Admin\Resources\Signatures\RelationManagers;

use App\Actions\Signatures\PersistSignatureSignerAction;
use App\Enums\SignatureRequestStatus;
use App\Models\SignatureRequest;
use App\Models\SignatureRequestSigner;
use App\Support\Filament\FormatBackedEnumState;
use App\Support\Filament\RemapValidationToMountedAction;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SignatureSignersRelationManager extends RelationManager
{
    protected static string $relationship = 'signers';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('signature-signers.plural_label');
    }

    public function table(Table $table): Table
    {
        /** @var SignatureRequest $request */
        $request = $this->getOwnerRecord();

        return $table
            ->recordTitleAttribute('email')
            ->columns([
                TextColumn::make('name')->label(__('signature-signers.fields.name'))->searchable(),
                TextColumn::make('email')->label(__('signature-signers.fields.email'))->searchable(),
                TextColumn::make('status')
                    ->label(__('signature-signers.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => __('signature-signers.status.'.FormatBackedEnumState::value($state))),
                TextColumn::make('user.email')->label(__('signature-signers.fields.user'))->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('signature-signers.actions.add'))
                    ->visible(fn (): bool => $request->status === SignatureRequestStatus::Draft)
                    ->form([
                        Select::make('user_id')
                            ->label(__('signature-signers.fields.user'))
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'email',
                                modifyQueryUsing: function (Builder $query): Builder {
                                    $user = auth()->user();
                                    if (! $user) {
                                        return $query->whereRaw('1 = 0');
                                    }
                                    if ($user->isSuperAdmin()) {
                                        return $query;
                                    }

                                    return $query->where('tenant_id', $user->tenant_id);
                                },
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        TextInput::make('name')
                            ->label(__('signature-signers.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('signature-signers.fields.email'))
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('signing_order')
                            ->label(__('signature-signers.fields.signing_order'))
                            ->numeric()
                            ->nullable(),
                    ])
                    ->using(fn (array $data) => RemapValidationToMountedAction::run(
                        fn () => app(PersistSignatureSignerAction::class)->create(auth()->user(), $request, $data),
                        $this,
                    )),
            ])
            ->actions([
                EditAction::make()
                    ->label(__('actions.edit'))
                    ->visible(fn (SignatureRequestSigner $record): bool => $record->request->status === SignatureRequestStatus::Draft)
                    ->using(fn (SignatureRequestSigner $record, array $data) => RemapValidationToMountedAction::run(
                        fn () => app(PersistSignatureSignerAction::class)->update(auth()->user(), $record, $data),
                        $this,
                    )),
            ])
            ->bulkActions([]);
    }
}
