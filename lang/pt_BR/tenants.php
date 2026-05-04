<?php

return [
    'navigation_group' => 'Organização',
    'model_label' => 'Tenant',
    'plural_label' => 'Tenants',
    'navigation_label' => 'Tenants',
    'section_main' => 'Dados do tenant',
    'fields' => [
        'name' => 'Nome',
        'slug' => 'Identificador (slug)',
        'document' => 'Documento',
        'status' => 'Estado',
        'created_at' => 'Criado em',
    ],
    'status' => [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
        'suspended' => 'Suspenso',
    ],
    'filters' => [
        'status' => 'Estado',
    ],
];
