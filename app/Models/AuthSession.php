<?php

namespace App\Models;

use App\Enums\AuthSessionStatus;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\AuthSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthSession extends Model
{
    /** @use HasFactory<AuthSessionFactory> */
    use BelongsToTenant, HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'auth_sessions';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'login_at',
        'logout_at',
        'last_activity_at',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AuthSessionStatus::class,
            'login_at' => 'datetime',
            'logout_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === AuthSessionStatus::Active;
    }
}
