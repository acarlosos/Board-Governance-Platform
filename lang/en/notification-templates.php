<?php

return [
    'navigation_group' => 'Admin',
    'navigation_label' => 'Notification templates',
    'model_label' => 'Template',
    'plural_label' => 'Templates',

    'sections' => [
        'data' => 'Data',
        'content' => 'Content',
        'variables' => 'Variables',
        'organization' => 'Organization',
    ],

    'fields' => [
        'tenant' => 'Organization',
        'key' => 'Key',
        'name' => 'Name',
        'subject' => 'Subject',
        'body' => 'Body',
        'locale' => 'Locale',
        'channel' => 'Channel',
        'status' => 'Status',
        'variables' => 'Variables',
        'variable_key' => 'Variable',
        'variable_description' => 'Description',
        'updated_at' => 'Updated at',
    ],

    'filters' => [
        'status' => 'Status',
    ],

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'helper' => [
        'global_or_tenant' => 'Global templates (no organization) act as fallback. Tenant templates override by key/locale/channel.',
    ],

    'validation' => [
        'tenant_required' => 'Tenant is required.',
        'tenant_mismatch' => 'Record does not belong to the current tenant.',
        'cannot_edit_global' => 'You cannot edit global templates.',
    ],
];

