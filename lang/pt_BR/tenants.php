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
        'slug_helper_create' => 'Gerado automaticamente a partir do nome; pode ajustar antes de guardar. Deve ser único.',
        'slug_helper_edit' => 'O identificador não pode ser alterado após a criação.',
        'document' => 'Documento',
        'status' => 'Estado',
        'created_at' => 'Criado em',
    ],
    'status' => [
        'active' => 'Ativo',
        'inactive' => 'Inativo',
        'suspended' => 'Suspenso',
    ],
    'filters' => [
        'status' => 'Estado',
    ],
];
