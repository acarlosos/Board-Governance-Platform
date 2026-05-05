<?php

namespace App\Models;

use App\Enums\BoardMemberRole;
use App\Enums\BoardMemberStatus;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\BoardMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoardMember extends Model
{
    /** @use HasFactory<BoardMemberFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'board_members';

    protected $fillable = [
        'tenant_id',
        'board_id',
        'user_id',
        'role',
        'status',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => BoardMemberRole::class,
            'status' => BoardMemberStatus::class,
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

