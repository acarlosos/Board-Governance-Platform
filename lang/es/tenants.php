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
        'slug_helper_create' => 'Generado automáticamente desde el nombre; puede ajustarlo antes de guardar. Debe ser único.',
        'slug_helper_edit' => 'El identificador no se puede cambiar después de la creación.',
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
