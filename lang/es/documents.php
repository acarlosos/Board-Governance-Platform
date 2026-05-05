<?php

return [
    'navigation_group' => 'Gobernanza',
    'navigation_label' => 'Documentos',

    'model_label' => 'Documento',
    'plural_label' => 'Documentos',

    'sections' => [
        'main' => 'Documento',
        'context' => 'Contexto',
        'organization' => 'Organización',
    ],

    'fields' => [
        'title' => 'Título',
        'description' => 'Descripción',
        'category' => 'Categoría',
        'status' => 'Estado',
        'board' => 'Consejo',
        'meeting' => 'Reunión',
        'initial_file' => 'Archivo (versión inicial)',
    ],

    'helpers' => [
        'tenant_only_super_admin' => 'Solo el super admin puede elegir el tenant manualmente.',
        'initial_file' => 'La carga inicial crea la versión 1 en almacenamiento privado, separado por tenant.',
    ],

    'filters' => [
        'status' => 'Estado',
    ],

    'actions' => [
        'publish' => 'Publicar',
        'archive' => 'Archivar',
        'download_current' => 'Descargar versión actual',
    ],

    'statuses' => [
        'draft' => 'Borrador',
        'published' => 'Publicado',
        'archived' => 'Archivado',
    ],

    'validation' => [
        'attributes' => [
            'tenant' => 'Tenant',
            'board' => 'Consejo',
            'meeting' => 'Reunión',
            'title' => 'Título',
            'description' => 'Descripción',
            'category' => 'Categoría',
            'status' => 'Estado',
        ],
        'tenant_required' => 'El tenant es obligatorio.',
        'tenant_mismatch' => 'No puedes acceder a recursos de otro tenant.',
        'board_must_belong_to_tenant' => 'El consejo debe pertenecer al mismo tenant.',
        'meeting_must_belong_to_tenant' => 'La reunión debe pertenecer al mismo tenant.',
        'board_must_match_meeting_board' => 'El consejo debe ser el mismo de la reunión seleccionada.',
    ],
];

