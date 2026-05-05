<?php

return [
    'fields' => [
        'file' => 'Archivo',
        'version_number' => 'Versión',
        'original_name' => 'Nombre original',
        'mime_type' => 'Tipo',
        'size' => 'Tamaño',
    ],

    'actions' => [
        'upload' => 'Subir nueva versión',
        'download' => 'Descargar',
    ],

    'validation' => [
        'invalid_extension' => 'La extensión del archivo no está permitida.',
        'max_size_exceeded' => 'El archivo excede el tamaño máximo permitido.',
        'unreadable_file' => 'No se pudo leer el archivo subido.',
    ],
];

