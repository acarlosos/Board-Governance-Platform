<?php

namespace App\Models;

use App\Enums\SignatureSignerStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SignatureRequestSigner extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'signature_request_signers';

    protected $fillable = [
        'tenant_id',
        'signature_request_id',
        'user_id',
        'name',
        'email',
        'status',
        'signing_order',
        'signed_at',
        'rejected_at',
        'rejection_reason',
        'external_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => SignatureSignerStatus::class,
            'signing_order' => 'integer',
            'signed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(SignatureRequest::class, 'signature_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

