<?php

return [
    'api' => [
        'token_expiration_days' => (int) env('BOARD_API_TOKEN_EXPIRATION_DAYS', 30),
    ],
    'documents' => [
        'disk' => env('BOARD_DOCUMENTS_DISK', 'local'),
        'base_path' => 'private/tenants',

        'max_upload_size_kb' => (int) env('BOARD_DOCUMENTS_MAX_UPLOAD_SIZE_KB', 20480), // 20 MB

        /**
         * Extensões permitidas (whitelist).
         * A validação por extensão é usada para evitar uploads inesperados; mime é capturado como metadado.
         */
        'allowed_extensions' => [
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'ppt',
            'pptx',
            'png',
            'jpg',
            'jpeg',
        ],
    ],

    /*
    | Executive Dashboard snapshot (Fase 19A) — TTL e versão alinham-se a D1/D8 em docs/features/dashboard.md
    */
    'dashboard' => [
        'snapshot_version' => 'v1',
        'cache_ttl_seconds' => 60,
        'cache_stale_seconds' => 60,
        'cache_expire_seconds' => 120,
        'priorities_max' => 10,
        'activity_max' => 15,
    ],
];

