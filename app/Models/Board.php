<?php

namespace App\Models;

use App\Enums\BoardStatus;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\BoardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Board extends Model
{
    /** @use HasFactory<BoardFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => BoardStatus::class,
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

    public function boardMembers(): HasMany
    {
        return $this->hasMany(BoardMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'board_members')
            ->withPivot(['role', 'status', 'joined_at', 'left_at', 'deleted_at'])
            ->withTimestamps();
    }
}

