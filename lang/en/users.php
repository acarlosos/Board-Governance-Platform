<?php

return [
    'navigation_group' => 'Organization',
    'model_label' => 'User',
    'plural_label' => 'Users',
    'navigation_label' => 'Users',
    'section_account' => 'Account',
    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'tenant' => 'Tenant',
        'locale' => 'Locale',
        'status' => 'Status',
        'roles' => 'Roles',
        'roles_helper' => 'Select at least one role.',
        'is_super_admin' => 'Platform super administrator',
        'created_at' => 'Created at',
        'tenant_placeholder' => '—',
    ],
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'suspended' => 'Suspended',
    ],
    'roles' => [
        'super_admin' => 'Super administrator',
        'tenant_admin' => 'Tenant administrator',
        'board_member' => 'Board member',
        'executive' => 'Executive',
        'guest' => 'Guest',
    ],
    'filters' => [
        'tenant' => 'Tenant',
        'status' => 'Status',
        'role' => 'Role',
    ],
    'validation' => [
        'attributes' => [
            'name' => 'name',
            'email' => 'email',
            'password' => 'password',
            'roles' => 'roles',
        ],
        'tenant_mismatch' => 'This operation is not allowed for this tenant.',
        'roles_not_allowed' => 'One or more roles are not allowed for your profile.',
    ],
];
