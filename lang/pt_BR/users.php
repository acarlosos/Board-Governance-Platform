<?php

return [
    'navigation_group' => 'Organização',
    'model_label' => 'Utilizador',
    'plural_label' => 'Utilizadores',
    'navigation_label' => 'Utilizadores',
    'section_account' => 'Conta',
    'fields' => [
        'name' => 'Nome',
        'email' => 'E-mail',
        'password' => 'Palavra-passe',
        'tenant' => 'Tenant',
        'locale' => 'Idioma',
        'status' => 'Estado',
        'roles' => 'Papéis',
        'roles_helper' => 'Seleccione pelo menos um papel.',
        'is_super_admin' => 'Super administrador (plataforma)',
        'created_at' => 'Criado em',
        'tenant_placeholder' => '—',
    ],
    'status' => [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
        'suspended' => 'Suspenso',
    ],
    'roles' => [
        'super_admin' => 'Super administrador',
        'tenant_admin' => 'Administrador do tenant',
        'board_member' => 'Membro do conselho',
        'executive' => 'Executivo',
        'guest' => 'Convidado',
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
    ],
];
