<?php

namespace App\Http\Controllers;

use App\Actions\Documents\RecordDocumentAccessAction;
use App\Models\Document;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    public function download(int $document): StreamedResponse|BinaryFileResponse|Response
    {
        $document = Document::query()
            ->withoutGlobalScopes() // reason: route model binding por id; authorize('view') garante tenant + permissão.
            ->findOrFail($document);

        $this->authorize('view', $document);

        $version = $document->currentVersion;
        abort_if(! $version, 404);

        RecordDocumentAccessAction::run($document, $version, 'download');

        try {
            $disk = Storage::disk($version->disk);

            $size = $version->size ?? null;
            if ($size === null) {
                $size = $disk->size($version->file_path);
            }

            if ($size > (10 * 1024 * 1024)) {
                return response()->streamDownload(function () use ($disk, $version): void {
                    $stream = $disk->readStream($version->file_path);
                    if (! is_resource($stream)) {
                        throw new \RuntimeException('Unreadable stream');
                    }
                    fpassthru($stream);
                    fclose($stream);
                }, $version->original_name);
            }

            return response()->download(
                $disk->path($version->file_path),
                $version->original_name,
            );
        } catch (\Throwable $e) {
            // Não vazar paths internos ou detalhes técnicos.
            return response('Erro ao processar o arquivo.', 500);
        }
    }
}

