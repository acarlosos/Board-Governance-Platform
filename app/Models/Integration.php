<?php

namespace App\Models;

use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Enums\IntegrationTestStatus;
use App\Enums\IntegrationType;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\IntegrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Integration extends Model
{
    /** @use HasFactory<IntegrationFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'integrations';

    protected $fillable = [
        'tenant_id',
        'type',
        'provider',
        'name',
        'status',
        'config',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => IntegrationType::class,
            'provider' => IntegrationProvider::class,
            'status' => IntegrationStatus::class,
            'config' => 'encrypted:array',
            'last_tested_at' => 'datetime',
            'last_test_status' => IntegrationTestStatus::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(IntegrationLog::class);
    }
}

