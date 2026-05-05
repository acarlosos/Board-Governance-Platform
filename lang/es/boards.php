<?php

return [
    'navigation_group' => 'Gobernanza',
    'model_label' => 'Consejo',
    'plural_label' => 'Consejos',
    'navigation_label' => 'Consejos',

    'section_main' => 'Datos del consejo',
    'section_organization' => 'Organización',

    'fields' => [
        'name' => 'Nombre',
        'description' => 'Descripción',
        'status' => 'Estado',
        'tenant' => 'Tenant',
        'active_members' => 'Miembros activos',
        'created_at' => 'Creado el',
    ],

    'status' => [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
        'archived' => 'Archivado',
    ],

    'filters' => [
        'tenant' => 'Tenant',
        'status' => 'Estado',
    ],

    'actions' => [
        'archive' => 'Archivar',
    ],

    'validation' => [
        'tenant_required' => 'El tenant es obligatorio.',
        'tenant_mismatch' => 'El tenant seleccionado no coincide con su contexto.',
        'attributes' => [
            'tenant' => 'tenant',
            'name' => 'nombre',
            'description' => 'descripción',
            'status' => 'estado',
        ],
    ],
];
