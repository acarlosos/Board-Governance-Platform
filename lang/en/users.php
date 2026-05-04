<?php

return [
    'navigation_group' => 'Organization',
    'model_label' => 'User',
    'plural_label' => 'Users',
    'navigation_label' => 'Users',
    'section_account' => 'Account',
    'section_organization' => 'Organization',
    'section_permissions' => 'Permissions',
    'section_preferences' => 'Preferences',
    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'password_helper_edit' => 'Leave blank to keep the current password.',
        'tenant' => 'Tenant',
        'locale' => 'Locale',
        'status' => 'Status',
        'roles' => 'Roles',
        'roles_helper' => 'Select at least one role (required), unless you only enable “Platform super administrator”.',
        'is_super_admin' => 'Platform super administrator',
        'created_at' => 'Created at',
        'tenant_placeholder' => '—',
    ],
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'suspended' => 'Suspended',
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
        'roles_required_unless_super' => 'Select at least one role, or enable “Platform super administrator”.',
    ],
];
