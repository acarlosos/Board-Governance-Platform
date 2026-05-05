<?php

namespace App\Models;

use App\Enums\MeetingAgendaItemStatus;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\MeetingAgendaItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeetingAgendaItem extends Model
{
    /** @use HasFactory<MeetingAgendaItemFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'meeting_agenda_items';

    protected $fillable = [
        'tenant_id',
        'meeting_id',
        'title',
        'description',
        'order_column',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => MeetingAgendaItemStatus::class,
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
}

