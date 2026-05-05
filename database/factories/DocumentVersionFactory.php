<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentVersion>
 */
class DocumentVersionFactory extends Factory
{
    protected $model = DocumentVersion::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'document_id' => Document::factory()->state(fn (array $attributes): array => [
                'tenant_id' => $attributes['tenant_id'],
            ]),
            'version_number' => 1,
            'original_name' => 'file.pdf',
            'file_path' => 'private/tenants/0/documents/0/versions/1/file.pdf',
            'disk' => 'local',
            'mime_type' => 'application/pdf',
            'size' => 123,
            'checksum' => null,
            'uploaded_by' => null,
        ];
    }
}

