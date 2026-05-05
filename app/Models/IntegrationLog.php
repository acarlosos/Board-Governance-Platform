<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationLog extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    protected $table = 'integration_logs';

    protected $fillable = [
        'tenant_id',
        'integration_id',
        'action',
        'status',
        'message',
        'context',
        'user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

