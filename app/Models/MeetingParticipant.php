<?php

namespace App\Models;

use App\Enums\MeetingParticipantRole;
use App\Enums\MeetingParticipantStatus;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\MeetingParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeetingParticipant extends Model
{
    /** @use HasFactory<MeetingParticipantFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'meeting_participants';

    protected $fillable = [
        'tenant_id',
        'meeting_id',
        'user_id',
        'role',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => MeetingParticipantRole::class,
            'status' => MeetingParticipantStatus::class,
            'responded_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

