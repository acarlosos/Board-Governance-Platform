<?php

return [
    'navigation_group' => 'Security',
    'model_label' => 'Audit log',
    'plural_label' => 'Audit logs',
    'navigation_label' => 'Audit',

    'fields' => [
        'action' => 'Action',
        'tenant' => 'Tenant',
        'user' => 'User',
        'auditable_type' => 'Resource',
        'auditable_id' => 'Resource ID',
        'ip_address' => 'IP',
        'created_at' => 'Created at',
    ],

    'filters' => [
        'tenant' => 'Tenant',
        'user' => 'User',
        'action' => 'Action',
        'auditable_type' => 'Resource',
        'period' => 'Period',
        'from' => 'From',
        'until' => 'Until',
    ],

    'actions' => [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
        'status_changed' => 'Status changed',
        'login' => 'Login',
        'logout' => 'Logout',
    ],

    'auditable_types' => [
        'App\\Models\\Tenant' => 'Tenant',
        'App\\Models\\User' => 'User',
    ],
];

