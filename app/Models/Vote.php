<?php

namespace App\Models;

use App\Enums\VoteStatus;
use App\Enums\VoteType;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\VoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vote extends Model
{
    /** @use HasFactory<VoteFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'votes';

    protected $fillable = [
        'tenant_id',
        'meeting_id',
        'title',
        'description',
        'type',
        'status',
        'quorum_required',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => VoteType::class,
            'status' => VoteStatus::class,
            'quorum_required' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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

    public function options(): HasMany
    {
        return $this->hasMany(VoteOption::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(VoteResponse::class);
    }
}

