<?php

namespace App\Actions\Minutes;

use App\Enums\MinuteStatus;
use App\Models\Minute;
use App\Models\MinuteVersion;
use App\Models\User;
use App\Support\Minutes\MinuteContent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateMinuteVersionAction
{
    /**
     * @param  array{content:string, changes_summary?:string|null}  $data
     */
    public function create(User $actor, Minute $minute, array $data): MinuteVersion
    {
        $this->assertTenantAccess($actor, $minute);

        if ($minute->status !== MinuteStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => __('minutes.validation.version_only_in_draft'),
            ]);
        }

        if (! isset($data['content']) || ! is_string($data['content']) || MinuteContent::isBlank($data['content'])) {
            throw ValidationException::withMessages([
                'content' => __('minutes.validation.content_required'),
            ]);
        }

        $attempts = 0;

        while (true) {
            try {
                return DB::transaction(function () use ($actor, $minute, $data): MinuteVersion {
                    // Lock do "pai" para serializar criação de versões.
                    Minute::query()
                        ->withoutGlobalScopes() // reason: lock pessimista do minuto já validado; whereKey fixa o registo.
                        ->whereKey($minute->getKey())
                        ->lockForUpdate()
                        ->first();

                    $last = MinuteVersion::withTrashed()
                        ->where('tenant_id', $minute->tenant_id)
                        ->where('minute_id', $minute->id)
                        ->orderByDesc('version_number')
                        ->lockForUpdate()
                        ->first();

                    $versionNumber = ((int) ($last?->version_number ?? 0)) + 1;

                    $version = MinuteVersion::query()->create([
                        'tenant_id' => $minute->tenant_id,
                        'minute_id' => $minute->id,
                        'version_number' => $versionNumber,
                        'content' => $data['content'],
                        'changes_summary' => $data['changes_summary'] ?? null,
                        'created_by' => $actor->id,
                    ]);

                    $minute->content = $data['content'];
                    $minute->current_version_id = $version->id;
                    $minute->save();

                    return $version->fresh();
                });
            } catch (QueryException $e) {
                // Defesa extra contra concorrência: se bater na unique, tentamos novamente.
                $attempts++;
                if ($attempts >= 3) {
                    throw $e;
                }
            }
        }
    }

    private function assertTenantAccess(User $actor, Minute $minute): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->tenant_id === null || (int) $actor->tenant_id !== (int) $minute->tenant_id) {
            throw ValidationException::withMessages([
                'tenant_id' => __('minutes.validation.tenant_mismatch'),
            ]);
        }
    }
}
