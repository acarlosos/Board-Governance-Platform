<?php

return [
    'navigation_group' => 'Organização',
    'model_label' => 'Usuário',
    'plural_label' => 'Usuários',
    'navigation_label' => 'Usuários',
    'section_account' => 'Conta',
    'section_organization' => 'Organização',
    'section_permissions' => 'Permissões',
    'section_preferences' => 'Preferências',
    'fields' => [
        'name' => 'Nome',
        'email' => 'E-mail',
        'password' => 'Senha',
        'password_helper_edit' => 'Deixe em branco para manter a senha atual.',
        'tenant' => 'Tenant',
        'locale' => 'Idioma',
        'status' => 'Estado',
        'roles' => 'Papéis',
        'roles_helper' => 'Selecione pelo menos um papel (obrigatório), exceto se ativar apenas «Super administrador (plataforma)».',
        'is_super_admin' => 'Super administrador (plataforma)',
        'created_at' => 'Criado em',
        'tenant_placeholder' => '—',
    ],
    'status' => [
        'active' => 'Ativo',
        'inactive' => 'Inativo',
        'suspended' => 'Suspenso',
    ],
    'filters' => [
        'tenant' => 'Tenant',
        'status' => 'Estado',
        'role' => 'Papel',
    ],
    'validation' => [
        'attributes' => [
            'name' => 'nome',
            'email' => 'e-mail',
            'password' => 'senha',
            'roles' => 'papéis',
        ],
        'tenant_mismatch' => 'Operação não permitida para este tenant.',
        'roles_not_allowed' => 'Um ou mais papéis não são permitidos para o seu perfil.',
        'roles_required_unless_super' => 'Selecione pelo menos um papel, ou ative «Super administrador (plataforma)».',
    ],
];
