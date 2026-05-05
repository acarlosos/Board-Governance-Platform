<?php

return [
    'navigation_group' => 'Admin',
    'navigation_label' => 'Templates de notificação',
    'model_label' => 'Template',
    'plural_label' => 'Templates',

    'sections' => [
        'data' => 'Dados',
        'content' => 'Conteúdo',
        'variables' => 'Variáveis',
        'organization' => 'Organização',
    ],

    'fields' => [
        'tenant' => 'Organização',
        'key' => 'Chave',
        'name' => 'Nome',
        'subject' => 'Assunto',
        'body' => 'Corpo',
        'locale' => 'Idioma',
        'channel' => 'Canal',
        'status' => 'Status',
        'variables' => 'Variáveis',
        'variable_key' => 'Variável',
        'variable_description' => 'Descrição',
        'updated_at' => 'Atualizado em',
    ],

    'filters' => [
        'status' => 'Status',
    ],

    'status' => [
        'active' => 'Ativo',
        'inactive' => 'Inativo',
    ],

    'helper' => [
        'global_or_tenant' => 'Templates globais (sem organização) funcionam como fallback. Templates do tenant sobrescrevem por chave/idioma/canal.',
    ],

    'validation' => [
        'tenant_required' => 'Tenant é obrigatório.',
        'tenant_mismatch' => 'Registro não pertence ao tenant atual.',
        'cannot_edit_global' => 'Você não pode editar templates globais.',
    ],
];

