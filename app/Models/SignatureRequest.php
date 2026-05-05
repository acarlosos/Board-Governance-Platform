<?php

namespace App\Models;

use App\Enums\SignatureProvider;
use App\Enums\SignatureRequestStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SignatureRequest extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'signature_requests';

    protected $fillable = [
        'tenant_id',
        'signable_type',
        'signable_id',
        'provider',
        'integration_id',
        'title',
        'message',
        'status',
        'requested_by',
        'requested_at',
        'completed_at',
        'cancelled_at',
        'external_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'provider' => SignatureProvider::class,
            'status' => SignatureRequestStatus::class,
            'metadata' => 'array',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function signers(): HasMany
    {
        return $this->hasMany(SignatureRequestSigner::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SignatureEvent::class);
    }
}

