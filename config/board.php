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

        // Coexistência widgets executivos (19A.7) vs legados (Fase 14).
        // false (default) → legados visíveis, executivos ocultos.
        // true            → executivos visíveis, legados ocultos.
        // Remover esta flag e os legados em 19B.6.
        'use_executive_widgets' => env('BGP_DASHBOARD_USE_EXECUTIVE_WIDGETS', false),

        // L3 projection (19B.3): leitura via tabela quando true; job/comando populam independentemente.
        'use_projection' => env('BGP_DASHBOARD_USE_PROJECTION', false),
    ],
];
