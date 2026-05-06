<?php

namespace App\Services\Api;

use Illuminate\Support\Str;

final class RequestIdService
{
    private const ATTRIBUTE = '_api_request_id';

    public function get(): string
    {
        $request = request();

        $existing = $request?->attributes?->get(self::ATTRIBUTE);
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $id = (string) Str::uuid();
        $request?->attributes?->set(self::ATTRIBUTE, $id);

        return $id;
    }
}

