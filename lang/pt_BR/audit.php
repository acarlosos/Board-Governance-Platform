<?php

return [
    'navigation_group' => 'Segurança',
    'model_label' => 'Log de auditoria',
    'plural_label' => 'Logs de auditoria',
    'navigation_label' => 'Auditoria',

    'fields' => [
        'action' => 'Ação',
        'tenant' => 'Tenant',
        'user' => 'Utilizador',
        'auditable_type' => 'Recurso',
        'auditable_id' => 'ID do recurso',
        'ip_address' => 'IP',
        'created_at' => 'Criado em',
    ],

    'filters' => [
        'tenant' => 'Tenant',
        'user' => 'Utilizador',
        'action' => 'Ação',
        'auditable_type' => 'Recurso',
        'period' => 'Período',
        'from' => 'De',
        'until' => 'Até',
    ],

    'actions' => [
        'created' => 'Criado',
        'updated' => 'Actualizado',
        'deleted' => 'Eliminado',
        'restored' => 'Restaurado',
        'status_changed' => 'Status alterado',
        'login' => 'Login',
        'logout' => 'Logout',
    ],

    'auditable_types' => [
        'App\\Models\\Tenant' => 'Tenant',
        'App\\Models\\User' => 'Utilizador',
    ],
];

