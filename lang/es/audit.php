<?php

return [
    'navigation_group' => 'Seguridad',
    'model_label' => 'Log de auditoría',
    'plural_label' => 'Logs de auditoría',
    'navigation_label' => 'Auditoría',

    'fields' => [
        'action' => 'Acción',
        'tenant' => 'Tenant',
        'user' => 'Usuario',
        'auditable_type' => 'Recurso',
        'auditable_id' => 'ID del recurso',
        'ip_address' => 'IP',
        'created_at' => 'Creado el',
    ],

    'filters' => [
        'tenant' => 'Tenant',
        'user' => 'Usuario',
        'action' => 'Acción',
        'auditable_type' => 'Recurso',
        'period' => 'Período',
        'from' => 'Desde',
        'until' => 'Hasta',
    ],

    'actions' => [
        'created' => 'Creado',
        'updated' => 'Actualizado',
        'deleted' => 'Eliminado',
        'restored' => 'Restaurado',
        'status_changed' => 'Estado cambiado',
        'login' => 'Login',
        'logout' => 'Logout',
    ],

    'auditable_types' => [
        'App\\Models\\Tenant' => 'Tenant',
        'App\\Models\\User' => 'Usuario',
    ],
];

