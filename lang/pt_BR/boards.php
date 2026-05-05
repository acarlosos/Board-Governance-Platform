<?php

return [
    'navigation_group' => 'Governança',
    'model_label' => 'Conselho',
    'plural_label' => 'Conselhos',
    'navigation_label' => 'Conselhos',

    'section_main' => 'Dados do conselho',
    'section_organization' => 'Organização',

    'fields' => [
        'name' => 'Nome',
        'description' => 'Descrição',
        'status' => 'Estado',
        'tenant' => 'Tenant',
        'active_members' => 'Membros ativos',
        'created_at' => 'Criado em',
    ],

    'status' => [
        'active' => 'Ativo',
        'inactive' => 'Inativo',
        'archived' => 'Arquivado',
    ],

    'filters' => [
        'tenant' => 'Tenant',
        'status' => 'Estado',
    ],

    'actions' => [
        'archive' => 'Arquivar',
    ],

    'validation' => [
        'tenant_required' => 'O tenant é obrigatório.',
        'tenant_mismatch' => 'O tenant informado não corresponde ao seu contexto.',
        'attributes' => [
            'tenant' => 'tenant',
            'name' => 'nome',
            'description' => 'descrição',
            'status' => 'estado',
        ],
    ],
];
