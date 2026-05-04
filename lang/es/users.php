<?php

return [
    'navigation_group' => 'Organización',
    'model_label' => 'Usuario',
    'plural_label' => 'Usuarios',
    'navigation_label' => 'Usuarios',
    'section_account' => 'Cuenta',
    'fields' => [
        'name' => 'Nombre',
        'email' => 'Correo electrónico',
        'password' => 'Contraseña',
        'tenant' => 'Tenant',
        'locale' => 'Idioma',
        'status' => 'Estado',
        'roles' => 'Roles',
        'roles_helper' => 'Seleccione al menos un rol.',
        'is_super_admin' => 'Super administrador de la plataforma',
        'created_at' => 'Creado el',
        'tenant_placeholder' => '—',
    ],
    'status' => [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
        'suspended' => 'Suspendido',
    ],
    'roles' => [
        'super_admin' => 'Super administrador',
        'tenant_admin' => 'Administrador del tenant',
        'board_member' => 'Miembro del consejo',
        'executive' => 'Ejecutivo',
        'guest' => 'Invitado',
    ],
    'filters' => [
        'tenant' => 'Tenant',
        'status' => 'Estado',
        'role' => 'Rol',
    ],
    'validation' => [
        'attributes' => [
            'name' => 'nombre',
            'email' => 'correo',
            'password' => 'contraseña',
            'roles' => 'roles',
        ],
        'tenant_mismatch' => 'Operación no permitida para este tenant.',
        'roles_not_allowed' => 'Uno o más roles no están permitidos para su perfil.',
    ],
];
