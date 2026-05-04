<?php

return [
    'navigation_group' => 'Organização',
    'model_label' => 'Utilizador',
    'plural_label' => 'Utilizadores',
    'navigation_label' => 'Utilizadores',
    'section_account' => 'Conta',
    'section_organization' => 'Organização',
    'section_permissions' => 'Permissões',
    'section_preferences' => 'Preferências',
    'fields' => [
        'name' => 'Nome',
        'email' => 'E-mail',
        'password' => 'Palavra-passe',
        'password_helper_edit' => 'Deixe em branco para manter a palavra-passe actual.',
        'tenant' => 'Tenant',
        'locale' => 'Idioma',
        'status' => 'Estado',
        'roles' => 'Papéis',
        'roles_helper' => 'Seleccione pelo menos um papel (obrigatório), excepto se activar apenas «Super administrador (plataforma)».',
        'is_super_admin' => 'Super administrador (plataforma)',
        'created_at' => 'Criado em',
        'tenant_placeholder' => '—',
    ],
    'status' => [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
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
            'password' => 'palavra-passe',
            'roles' => 'papéis',
        ],
        'tenant_mismatch' => 'Operação não permitida para este tenant.',
        'roles_not_allowed' => 'Um ou mais papéis não são permitidos para o seu perfil.',
        'roles_required_unless_super' => 'Seleccione pelo menos um papel, ou active «Super administrador (plataforma)».',
    ],
];
