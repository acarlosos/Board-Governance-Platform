<?php

return [
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
];

