<?php

return [
    'fields' => [
        'file' => 'Arquivo',
        'version_number' => 'Versão',
        'original_name' => 'Nome original',
        'mime_type' => 'Tipo',
        'size' => 'Tamanho',
    ],

    'actions' => [
        'upload' => 'Enviar nova versão',
        'download' => 'Baixar',
    ],

    'validation' => [
        'invalid_extension' => 'Extensão de arquivo não permitida.',
        'max_size_exceeded' => 'Arquivo excede o tamanho máximo permitido.',
        'unreadable_file' => 'Não foi possível ler o arquivo enviado.',
    ],
];

