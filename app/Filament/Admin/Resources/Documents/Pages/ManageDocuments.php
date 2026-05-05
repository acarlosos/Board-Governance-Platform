<?php

namespace App\Filament\Admin\Resources\Documents\Pages;

use App\Actions\Documents\PersistDocumentAction;
use App\Actions\Documents\UploadDocumentVersionAction;
use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Models\Document;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ManageDocuments extends ManageRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('actions.create'))
                ->modalWidth(Width::FiveExtraLarge)
                ->using(function (array $data): Document {
                    /** @var TemporaryUploadedFile $file */
                    $file = $data['initial_file'];
                    unset($data['initial_file']);

                    $document = app(PersistDocumentAction::class)->create(auth()->user(), $data);

                    app(UploadDocumentVersionAction::class)->upload(auth()->user(), $document, $file);

                    return $document->fresh();
                }),
        ];
    }
}

