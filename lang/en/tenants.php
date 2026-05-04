<?php

return [
    'navigation_group' => 'Organization',
    'model_label' => 'Tenant',
    'plural_label' => 'Tenants',
    'navigation_label' => 'Tenants',
    'section_main' => 'Tenant details',
    'fields' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'document' => 'Document',
        'status' => 'Status',
        'created_at' => 'Created at',
    ],
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'suspended' => 'Suspended',
    ],
    'filters' => [
        'status' => 'Status',
    ],
];
