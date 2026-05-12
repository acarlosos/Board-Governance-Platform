<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\TenantDashboardSnapshotFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Projection L3 (Hero + Operations) por tenant e período — ver 19B.3.
 *
 * @property array<string, mixed> $payload
 */
final class TenantDashboardSnapshot extends Model
{
    /** @use HasFactory<TenantDashboardSnapshotFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'tenant_dashboard_snapshots';

    protected $fillable = [
        'tenant_id',
        'period',
        'payload',
        'is_stale',
        'refreshed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'is_stale' => 'boolean',
            'refreshed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeValid(Builder $builder): void
    {
        $builder->where('is_stale', false)
            ->where('refreshed_at', '>=', now()->subMinutes(10));
    }
}
