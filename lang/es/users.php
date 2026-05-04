<?php

return [
    'navigation_group' => 'Organización',
    'model_label' => 'Usuario',
    'plural_label' => 'Usuarios',
    'navigation_label' => 'Usuarios',
    'section_account' => 'Cuenta',
    'section_organization' => 'Organización',
    'section_permissions' => 'Permisos',
    'section_preferences' => 'Preferencias',
    'fields' => [
        'name' => 'Nombre',
        'email' => 'Correo electrónico',
        'password' => 'Contraseña',
        'password_helper_edit' => 'Déjelo en blanco para mantener la contraseña actual.',
        'tenant' => 'Tenant',
        'locale' => 'Idioma',
        'status' => 'Estado',
        'roles' => 'Roles',
        'roles_helper' => 'Seleccione al menos un rol (obligatorio), salvo que active solo «Super administrador de la plataforma».',
        'is_super_admin' => 'Super administrador de la plataforma',
        'created_at' => 'Creado el',
        'tenant_placeholder' => '—',
    ],
    'status' => [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
        'suspended' => 'Suspendido',
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
        'roles_required_unless_super' => 'Seleccione al menos un rol, o active «Super administrador de la plataforma».',
    ],
];
