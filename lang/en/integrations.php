<?php

return [
    'navigation_group' => 'Admin',
    'navigation_label' => 'Integrations',
    'model_label' => 'Integration',
    'plural_label' => 'Integrations',

    'sections' => [
        'data' => 'Data',
        'config' => 'Configuration',
        'test' => 'Test',
        'organization' => 'Organization',
    ],

    'fields' => [
        'tenant' => 'Organization',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
        'name' => 'Name',
        'type' => 'Type',
        'provider' => 'Provider',
        'status' => 'Status',
        'last_tested_at' => 'Last tested at',
        'last_test_status' => 'Last test status',
        'last_test_message' => 'Last test message',
    ],

    'filters' => [
        'status' => 'Status',
    ],

    'type' => [
        'email' => 'Email',
        'storage' => 'Storage',
        'signature' => 'Signature',
        'video_conference' => 'Video conference',
        'reporting' => 'Reporting',
        'identity' => 'Identity',
    ],

    'provider' => [
        'smtp' => 'SMTP',
        'microsoft_365' => 'Microsoft 365',
        'onedrive' => 'OneDrive',
        'docusign' => 'DocuSign',
        'teams' => 'Teams',
        'zoom' => 'Zoom',
        'looker_studio' => 'Looker Studio',
    ],

    'status' => [
        'inactive' => 'Inactive',
        'active' => 'Active',
        'error' => 'Error',
    ],

    'actions' => [
        'test' => 'Test connection',
        'enable' => 'Enable',
        'disable' => 'Disable',
    ],

    'logs' => [
        'created' => 'Integration created.',
        'updated' => 'Integration updated.',
        'enabled' => 'Integration enabled.',
        'disabled' => 'Integration disabled.',
    ],

    'helper' => [
        'keep_secret_if_empty' => 'Leave blank to keep the current value.',
    ],

    'validation' => [
        'tenant_required' => 'Tenant is required.',
        'tenant_mismatch' => 'Record does not belong to the current tenant.',
        'missing_required' => 'Missing required fields',
        'enable_requires_successful_test' => 'You can only enable after a successful test.',
    ],

    'test' => [
        'ok' => 'Configuration is valid (fake test).',
        'missing_required' => 'Missing required fields for provider.',
    ],

    'config' => [
        'host' => 'Host',
        'port' => 'Port',
        'username' => 'Username',
        'password' => 'Password',
        'encryption' => 'Encryption',
        'from_address' => 'From address',
        'from_name' => 'From name',
        'tenant_id' => 'Tenant ID',
        'client_id' => 'Client ID',
        'client_secret' => 'Client Secret',
        'redirect_uri' => 'Redirect URI',
        'root_folder' => 'Root folder',
        'account_id' => 'Account ID',
        'integration_key' => 'Integration Key',
        'user_id' => 'User ID',
        'private_key' => 'Private Key',
        'base_uri' => 'Base URI',
        'report_url' => 'Report URL',
    ],
];

