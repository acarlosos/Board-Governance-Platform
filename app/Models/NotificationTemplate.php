<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationTemplateStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'notification_templates';

    protected $fillable = [
        'tenant_id',
        'key',
        'name',
        'subject',
        'body',
        'locale',
        'channel',
        'status',
        'variables',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'status' => NotificationTemplateStatus::class,
            'variables' => 'array',
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
}

