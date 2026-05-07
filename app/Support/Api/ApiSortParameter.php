<?php

namespace App\Support\Api;

/**
 * Mini contrato de sort da API v1: um único query param `sort`, com prefixo `-` para descendente.
 * Ex.: `sort=-created_at`, `sort=name`.
 */
final class ApiSortParameter
{
    /**
     * @param  list<string>  $allowlist
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    public static function parse(?string $sort, array $allowlist, string $defaultField, string $defaultDirection = 'desc'): array
    {
        $sort = $sort !== null ? trim($sort) : '';
        if ($sort === '') {
            return [$defaultField, $defaultDirection === 'asc' ? 'asc' : 'desc'];
        }

        $direction = 'asc';
        $field = $sort;
        if (str_starts_with($sort, '-')) {
            $direction = 'desc';
            $field = substr($sort, 1);
        }

        if (! in_array($field, $allowlist, true)) {
            return [$defaultField, $defaultDirection === 'asc' ? 'asc' : 'desc'];
        }

        return [$field, $direction];
    }
}
