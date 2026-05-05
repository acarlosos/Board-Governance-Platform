<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\MinuteVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MinuteVersion extends Model
{
    /** @use HasFactory<MinuteVersionFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'minute_versions';

    protected $fillable = [
        'tenant_id',
        'minute_id',
        'version_number',
        'content',
        'changes_summary',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function minute(): BelongsTo
    {
        return $this->belongsTo(Minute::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

