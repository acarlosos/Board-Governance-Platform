<?php

namespace App\Models;

use App\Enums\MinuteApprovalStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinuteApproval extends Model
{
    use BelongsToTenant;

    protected $table = 'minute_approvals';

    protected $fillable = [
        'tenant_id',
        'minute_id',
        'user_id',
        'status',
        'approved_at',
        'rejected_at',
        'comments',
    ];

    protected function casts(): array
    {
        return [
            'status' => MinuteApprovalStatus::class,
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

