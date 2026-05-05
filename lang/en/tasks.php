<?php

return [
    'navigation_group' => 'Workflows',
    'navigation_label' => 'Tasks',
    'model_label' => 'Task',
    'plural_label' => 'Tasks',

    'sections' => [
        'data' => 'Data',
        'assignment' => 'Assignment',
        'due' => 'Due date & priority',
        'related' => 'Related to',
        'status' => 'Status',
    ],

    'fields' => [
        'title' => 'Title',
        'description' => 'Description',
        'status' => 'Status',
        'priority' => 'Priority',
        'due_date' => 'Due date',
        'assigned_to' => 'Assigned to',
        'related_type' => 'Related type',
        'related_id' => 'Related ID',
        'completed_at' => 'Completed at',
    ],

    'filters' => [
        'status' => 'Status',
    ],

    'status' => [
        'pending' => 'Pending',
        'in_progress' => 'In progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'priority' => [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],

    'actions' => [
        'start' => 'Start',
        'complete' => 'Complete',
        'cancel' => 'Cancel',
    ],

    'validation' => [
        'tenant_required' => 'Tenant is required.',
        'tenant_mismatch' => 'Record does not belong to the current tenant.',
        'assigned_must_belong_to_tenant' => 'Assigned user must belong to the same tenant.',
        'related_must_belong_to_tenant' => 'Related entity must belong to the same tenant.',
        'invalid_status_transition' => 'Invalid status transition.',
        'not_allowed' => 'You are not allowed to perform this action.',
    ],
];

