<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\VoteOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VoteOption extends Model
{
    /** @use HasFactory<VoteOptionFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'vote_options';

    protected $fillable = [
        'tenant_id',
        'vote_id',
        'title',
        'description',
        'order_column',
    ];

    protected function casts(): array
    {
        return [
            'order_column' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vote(): BelongsTo
    {
        return $this->belongsTo(Vote::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(VoteResponse::class);
    }
}

