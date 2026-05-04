<?php

return [
    'navigation_group' => 'Organización',
    'model_label' => 'Tenant',
    'plural_label' => 'Tenants',
    'navigation_label' => 'Tenants',
    'section_main' => 'Datos del tenant',
    'fields' => [
        'name' => 'Nombre',
        'slug' => 'Identificador (slug)',
        'document' => 'Documento',
        'status' => 'Estado',
        'created_at' => 'Creado el',
    ],
    'status' => [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
        'suspended' => 'Suspendido',
    ],
    'filters' => [
        'status' => 'Estado',
    ],
];
