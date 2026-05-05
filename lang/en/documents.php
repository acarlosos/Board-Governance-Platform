<?php

return [
    'navigation_group' => 'Governance',
    'navigation_label' => 'Documents',

    'model_label' => 'Document',
    'plural_label' => 'Documents',

    'sections' => [
        'main' => 'Document',
        'context' => 'Context',
        'organization' => 'Organization',
    ],

    'fields' => [
        'title' => 'Title',
        'description' => 'Description',
        'category' => 'Category',
        'status' => 'Status',
        'board' => 'Board',
        'meeting' => 'Meeting',
        'initial_file' => 'File (initial version)',
    ],

    'helpers' => [
        'tenant_only_super_admin' => 'Only super admin can choose tenant manually.',
        'initial_file' => 'The initial upload creates version 1 in private, tenant-separated storage.',
    ],

    'filters' => [
        'status' => 'Status',
    ],

    'actions' => [
        'publish' => 'Publish',
        'archive' => 'Archive',
        'download_current' => 'Download current version',
    ],

    'statuses' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ],

    'validation' => [
        'attributes' => [
            'tenant' => 'Tenant',
            'board' => 'Board',
            'meeting' => 'Meeting',
            'title' => 'Title',
            'description' => 'Description',
            'category' => 'Category',
            'status' => 'Status',
        ],
        'tenant_required' => 'Tenant is required.',
        'tenant_mismatch' => 'You cannot access resources from another tenant.',
        'board_must_belong_to_tenant' => 'Board must belong to the same tenant.',
        'meeting_must_belong_to_tenant' => 'Meeting must belong to the same tenant.',
        'board_must_match_meeting_board' => 'Board must match the selected meeting board.',
    ],
];

