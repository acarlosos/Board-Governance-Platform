<?php

return [
    'navigation_group' => 'Admin',
    'navigation_label' => 'Notifications',
    'model_label' => 'Notification',
    'plural_label' => 'Notifications',

    'fields' => [
        'title' => 'Title',
        'user' => 'User',
        'channel' => 'Channel',
        'status' => 'Status',
        'read_at' => 'Read at',
        'sent_at' => 'Sent at',
        'created_at' => 'Created at',
    ],

    'filters' => [
        'status' => 'Status',
    ],

    'actions' => [
        'mark_read' => 'Mark as read',
        'resend_fake' => 'Resend (fake)',
    ],

    'channel' => [
        'database' => 'Internal',
        'email' => 'Email',
    ],

    'status' => [
        'unread' => 'Unread',
        'read' => 'Read',
        'sent' => 'Sent',
        'failed' => 'Failed',
    ],

    'bell' => [
        'heading' => 'Notifications',
        'mark_all_read' => 'Mark all as read',
        'empty' => [
            'heading' => 'No notifications',
            'description' => 'When you have alerts, they will appear here.',
        ],
    ],

    'driver' => [
        'database_ok' => 'Internal notification recorded.',
        'email_fake_ok' => 'Fake email send (no SMTP).',
    ],

    'validation' => [
        'tenant_required' => 'Tenant is required.',
        'tenant_mismatch' => 'Record does not belong to the current tenant.',
        'user_must_belong_to_tenant' => 'User must belong to the same tenant.',
        'related_must_belong_to_tenant' => 'Related model must belong to the same tenant.',
        'not_allowed' => 'You are not allowed to perform this action.',
    ],
];
