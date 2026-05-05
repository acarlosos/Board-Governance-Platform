<?php

return [
    'navigation_group' => 'Governança',
    'navigation_label' => 'Documentos',

    'model_label' => 'Documento',
    'plural_label' => 'Documentos',

    'sections' => [
        'main' => 'Documento',
        'context' => 'Contexto',
        'organization' => 'Organização',
    ],

    'fields' => [
        'title' => 'Título',
        'description' => 'Descrição',
        'category' => 'Categoria',
        'status' => 'Status',
        'board' => 'Conselho',
        'meeting' => 'Reunião',
        'initial_file' => 'Arquivo (versão inicial)',
    ],

    'helpers' => [
        'tenant_only_super_admin' => 'Apenas super admin pode escolher tenant manualmente.',
        'initial_file' => 'O upload inicial cria a versão 1 em armazenamento privado, separado por tenant.',
    ],

    'filters' => [
        'status' => 'Status',
    ],

    'actions' => [
        'publish' => 'Publicar',
        'archive' => 'Arquivar',
        'download_current' => 'Baixar versão atual',
    ],

    'statuses' => [
        'draft' => 'Rascunho',
        'published' => 'Publicado',
        'archived' => 'Arquivado',
    ],

    'validation' => [
        'attributes' => [
            'tenant' => 'Tenant',
            'board' => 'Conselho',
            'meeting' => 'Reunião',
            'title' => 'Título',
            'description' => 'Descrição',
            'category' => 'Categoria',
            'status' => 'Status',
        ],
        'tenant_required' => 'Tenant é obrigatório.',
        'tenant_mismatch' => 'Você não pode acessar recursos de outro tenant.',
        'board_must_belong_to_tenant' => 'O conselho deve pertencer ao mesmo tenant.',
        'meeting_must_belong_to_tenant' => 'A reunião deve pertencer ao mesmo tenant.',
        'board_must_match_meeting_board' => 'O conselho deve ser o mesmo da reunião selecionada.',
    ],
];

