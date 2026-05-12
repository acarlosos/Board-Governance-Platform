<?php

namespace App\Services\Dashboard\Executive\Cache;

use App\Services\Dashboard\Executive\Observability\ExecutiveDashboardObservability;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Invalidação L1/L2 por segmento `t_{tenantId}` (D11: nunca `global` por evento de tenant).
 *
 * L2 usa {@see CacheRepository::flexible}; é obrigatório limpar também a chave auxiliar
 * `illuminate:cache:flexible:created:{key}` para evitar estado inconsistente.
 */
final class ExecutiveDashboardCacheInvalidator
{
    public const FLEXIBLE_CREATED_PREFIX = 'illuminate:cache:flexible:created:';

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly ExecutiveDashboardObservability $observability,
    ) {}

    public function invalidateForTenant(int $tenantId): void
    {
        $this->invalidateForSegment('t_'.$tenantId);
    }

    private function invalidateForSegment(string $cacheSegment): void
    {
        if ($cacheSegment === 'global') {
            return;
        }

        foreach (ExecutiveDashboardCacheKeys::allKeysForSegment($cacheSegment) as $key) {
            $this->cache->forget($key);
            if (str_ends_with($key, ':shared:plain')) {
                $this->cache->forget(self::FLEXIBLE_CREATED_PREFIX.$key);
            }
        }

        $this->observability->recordInvalidation();
    }
}
