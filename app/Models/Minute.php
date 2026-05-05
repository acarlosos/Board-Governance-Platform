<?php

namespace App\Models;

use App\Enums\MinuteStatus;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\MinuteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Minute extends Model
{
    /** @use HasFactory<MinuteFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'minutes';

    protected $fillable = [
        'tenant_id',
        'meeting_id',
        'title',
        'content',
        'status',
        'current_version_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => MinuteStatus::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(MinuteVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MinuteVersion::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(MinuteApproval::class);
    }
}

