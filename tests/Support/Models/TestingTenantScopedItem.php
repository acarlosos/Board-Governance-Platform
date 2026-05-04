<?php

namespace Tests\Support\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\Support\Database\Factories\TestingTenantScopedItemFactory;

/**
 * Modelo só para testes — tabela criada em runtime em {@see \Tests\Feature\MultitenancyTest}.
 */
class TestingTenantScopedItem extends Model
{
    /** @use HasFactory<TestingTenantScopedItemFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'testing_tenant_scoped_items';

    protected $fillable = [
        'tenant_id',
        'label',
    ];

    protected static function newFactory(): TestingTenantScopedItemFactory
    {
        return TestingTenantScopedItemFactory::new();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
