<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatureEvent extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    protected $table = 'signature_events';

    protected $fillable = [
        'tenant_id',
        'signature_request_id',
        'signer_id',
        'action',
        'status',
        'message',
        'context',
        'user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
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

    public function signer(): BelongsTo
    {
        return $this->belongsTo(SignatureRequestSigner::class, 'signer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

