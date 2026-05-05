<?php

return [
    'navigation_group' => 'Governance',
    'model_label' => 'Meeting',
    'plural_label' => 'Meetings',
    'navigation_label' => 'Meetings',

    'section_main' => 'Meeting details',
    'section_board' => 'Board',
    'section_dates' => 'Dates',
    'section_video' => 'Video conference',
    'section_organization' => 'Organization',

    'fields' => [
        'title' => 'Title',
        'description' => 'Description',
        'status' => 'Status',
        'board' => 'Board',
        'tenant' => 'Tenant',
        'scheduled_at' => 'Scheduled at',
        'starts_at' => 'Starts at',
        'ends_at' => 'Ends at',
        'video_conference_url' => 'Video conference URL',
        'participants' => 'Participants',
        'created_at' => 'Created at',
    ],

    'status' => [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'in_progress' => 'In progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'filters' => [
        'tenant' => 'Tenant',
        'board' => 'Board',
        'status' => 'Status',
        'period' => 'Period',
        'from' => 'From',
        'until' => 'Until',
    ],

    'actions' => [
        'start' => 'Start',
        'complete' => 'Complete',
        'cancel' => 'Cancel',
    ],

    'validation' => [
        'tenant_required' => 'Tenant is required.',
        'tenant_mismatch' => 'The selected tenant does not match your context.',
        'board_must_belong_to_tenant' => 'The board must belong to the same tenant as the meeting.',
        'invalid_status_transition' => 'Invalid status transition.',
        'attributes' => [
            'tenant' => 'tenant',
            'board' => 'board',
            'title' => 'title',
            'description' => 'description',
            'scheduled_at' => 'scheduled date',
            'starts_at' => 'start',
            'ends_at' => 'end',
            'video_conference_url' => 'video conference url',
            'status' => 'status',
        ],
    ],
];

