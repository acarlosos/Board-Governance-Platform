<?php

namespace App\Filament\Admin\Resources\Documents\RelationManagers;

use App\Actions\Documents\RecordDocumentAccessAction;
use App\Actions\Documents\UploadDocumentVersionAction;
use App\Enums\DocumentAccessAction;
use App\Models\Document;
use App\Models\DocumentVersion;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DocumentVersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version_number')
            ->columns([
                TextColumn::make('version_number')
                    ->label(__('document-versions.fields.version_number'))
                    ->sortable(),
                TextColumn::make('original_name')
                    ->label(__('document-versions.fields.original_name'))
                    ->searchable(),
                TextColumn::make('mime_type')
                    ->label(__('document-versions.fields.mime_type'))
                    ->toggleable(),
                TextColumn::make('size')
                    ->label(__('document-versions.fields.size'))
                    ->formatStateUsing(fn ($state): string => $state ? number_format(((int) $state) / 1024, 1).' KB' : '-')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('document-versions.actions.upload'))
                    ->form([
                        FileUpload::make('file')
                            ->label(__('document-versions.fields.file'))
                            ->storeFiles(false)
                            ->required(),
                    ])
                    ->using(function (array $data): DocumentVersion {
                        /** @var Document $document */
                        $document = $this->getOwnerRecord();

                        /** @var TemporaryUploadedFile $file */
                        $file = $data['file'];

                        return app(UploadDocumentVersionAction::class)->upload(auth()->user(), $document, $file);
                    }),
            ])
            ->actions([
                Action::make('download')
                    ->label(__('document-versions.actions.download'))
                    ->action(function (DocumentVersion $record) {
                        /** @var Document $document */
                        $document = $this->getOwnerRecord();
                        app(RecordDocumentAccessAction::class)->record(
                            actor: auth()->user(),
                            document: $document,
                            action: DocumentAccessAction::Downloaded,
                            version: $record,
                        );

                        return response()->download(
                            Storage::disk($record->disk)->path($record->file_path),
                            $record->original_name,
                        );
                    }),
                DeleteAction::make()->label(__('actions.delete')),
                RestoreAction::make()->label(__('actions.restore')),
                ForceDeleteAction::make()->label(__('actions.force_delete')),
            ]);
    }
}

