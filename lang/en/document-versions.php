<?php

return [
    'fields' => [
        'file' => 'File',
        'version_number' => 'Version',
        'original_name' => 'Original name',
        'mime_type' => 'Type',
        'size' => 'Size',
    ],

    'actions' => [
        'upload' => 'Upload new version',
        'download' => 'Download',
    ],

    'validation' => [
        'invalid_extension' => 'File extension is not allowed.',
        'max_size_exceeded' => 'File exceeds the maximum allowed size.',
        'unreadable_file' => 'Could not read the uploaded file.',
    ],
];

