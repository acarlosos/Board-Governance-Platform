<?php

namespace App\Actions\Documents;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class UploadDocumentVersionAction
{
    public function upload(User $actor, Document $document, UploadedFile $file): DocumentVersion
    {
        $this->assertTenantAccess($actor, $document);
        $this->assertFileAllowed($file);

        $disk = (string) config('board.documents.disk', 'local');
        $basePath = (string) config('board.documents.base_path', 'private/tenants');

        return DB::transaction(function () use ($document, $actor, $file, $disk, $basePath): DocumentVersion {
            $max = (int) DocumentVersion::withTrashed()
                ->where('tenant_id', $document->tenant_id)
                ->where('document_id', $document->id)
                ->lockForUpdate()
                ->max('version_number');

            $versionNumber = $max + 1;

            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
            $safeName = (string) Str::uuid().($extension ? '.'.$extension : '');

            $relativePath = implode('/', [
                trim($basePath, '/'),
                (string) $document->tenant_id,
                'documents',
                (string) $document->id,
                'versions',
                (string) $versionNumber,
                $safeName,
            ]);

            $contents = method_exists($file, 'getContent')
                ? $file->getContent()
                : file_get_contents($file->getPathname());

            if ($contents === false) {
                throw ValidationException::withMessages([
                    'file' => __('document-versions.validation.unreadable_file'),
                ]);
            }

            Storage::disk($disk)->put($relativePath, $contents);

            $checksum = null;
            $absolutePath = Storage::disk($disk)->path($relativePath);
            if (is_string($absolutePath) && file_exists($absolutePath)) {
                $checksum = @hash_file('sha256', $absolutePath) ?: null;
            } else {
                $checksum = hash('sha256', Storage::disk($disk)->get($relativePath));
            }

            $version = DocumentVersion::query()->create([
                'tenant_id' => $document->tenant_id,
                'document_id' => $document->id,
                'version_number' => $versionNumber,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $relativePath,
                'disk' => $disk,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'checksum' => $checksum,
                'uploaded_by' => $actor->id,
            ]);

            $document->current_version_id = $version->id;
            $document->save();

            return $version->fresh();
        });
    }

    private function assertFileAllowed(UploadedFile $file): void
    {
        $allowed = array_map('strtolower', (array) config('board.documents.allowed_extensions', []));
        $maxKb = (int) config('board.documents.max_upload_size_kb', 20480);

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        if ($extension === '' || ! in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages([
                'file' => __('document-versions.validation.invalid_extension'),
            ]);
        }

        $sizeKb = (int) ceil(((int) ($file->getSize() ?? 0)) / 1024);
        if ($sizeKb > $maxKb) {
            throw ValidationException::withMessages([
                'file' => __('document-versions.validation.max_size_exceeded'),
            ]);
        }
    }

    private function assertTenantAccess(User $actor, Document $document): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->tenant_id === null || (int) $actor->tenant_id !== (int) $document->tenant_id) {
            throw ValidationException::withMessages([
                'tenant_id' => __('documents.validation.tenant_mismatch'),
            ]);
        }
    }
}

