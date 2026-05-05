<?php

return [
    'navigation_group' => 'Governance',
    'model_label' => 'Board',
    'plural_label' => 'Boards',
    'navigation_label' => 'Boards',

    'section_main' => 'Board details',
    'section_organization' => 'Organization',

    'fields' => [
        'name' => 'Name',
        'description' => 'Description',
        'status' => 'Status',
        'tenant' => 'Tenant',
        'active_members' => 'Active members',
        'created_at' => 'Created at',
    ],

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'archived' => 'Archived',
    ],

    'filters' => [
        'tenant' => 'Tenant',
        'status' => 'Status',
    ],

    'actions' => [
        'archive' => 'Archive',
    ],

    'validation' => [
        'tenant_required' => 'Tenant is required.',
        'tenant_mismatch' => 'The selected tenant does not match your context.',
        'attributes' => [
            'tenant' => 'tenant',
            'name' => 'name',
            'description' => 'description',
            'status' => 'status',
        ],
    ],
];
