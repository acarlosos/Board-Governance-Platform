<?php

return [
    'navigation_group' => 'Governance',
    'navigation_label' => 'Minutes',
    'model_label' => 'Minute',
    'plural_label' => 'Minutes',

    'sections' => [
        'data' => 'Details',
        'content' => 'Content',
    ],

    'fields' => [
        'title' => 'Title',
        'meeting' => 'Meeting',
        'content' => 'Content',
        'status' => 'Status',
        'current_version' => 'Current version',
    ],

    'filters' => [
        'status' => 'Status',
    ],

    'actions' => [
        'submit_for_review' => 'Submit for review',
        'approve' => 'Approve',
        'reject' => 'Reject',
        'archive' => 'Archive',
        'reopen' => 'Reopen for editing',
    ],

    'notifications' => [
        'submitted_for_review' => 'Minute submitted for review.',
        'approved' => 'Approval recorded.',
        'rejected' => 'Rejection recorded.',
        'reopened' => 'Minute reopened for editing.',
        'archived' => 'Minute archived.',
    ],

    'status' => [
        'draft' => 'Draft',
        'in_review' => 'In review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'archived' => 'Archived',
    ],

    'validation' => [
        'tenant_required' => 'Tenant is required.',
        'tenant_mismatch' => 'You cannot access resources from another tenant.',
        'meeting_must_belong_to_tenant' => 'Meeting must belong to the same tenant.',
        'edit_only_in_draft' => 'Minutes can only be edited in draft.',
        'version_only_in_draft' => 'Versions can only be created in draft.',
        'submit_only_in_draft' => 'You can only submit for review from draft.',
        'approve_only_in_review' => 'You can only approve while in review.',
        'reject_only_in_review' => 'You can only reject while in review.',
        'reopen_only_in_rejected' => 'You can only reopen after rejection.',
        'archive_only_in_approved' => 'You can only archive after approval.',
        'already_approved' => 'You have already approved these minutes.',
        'already_rejected' => 'You have already rejected these minutes.',
        'invalid_status_transition' => 'Invalid status transition.',
        'no_participants_for_review' => 'Meeting has no eligible participants for review.',
        'not_eligible_to_approve' => 'You are not eligible to approve these minutes.',
        'content_required' => 'Content is required.',
    ],
];
