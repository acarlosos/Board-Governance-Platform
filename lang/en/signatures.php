<?php

return [
    'navigation_group' => 'Admin',
    'navigation_label' => 'Signatures',
    'model_label' => 'Signature request',
    'plural_label' => 'Signature requests',

    'sections' => [
        'data' => 'Data',
        'signable' => 'Document/Minute',
        'organization' => 'Organization',
        'timestamps' => 'Timestamps',
    ],

    'fields' => [
        'title' => 'Title',
        'message' => 'Message',
        'provider' => 'Provider',
        'integration' => 'Integration',
        'status' => 'Status',
        'signable' => 'Signable',
        'signable_type' => 'Type',
        'signable_id' => 'ID',
        'requested_by' => 'Requested by',
        'requested_at' => 'Requested at',
        'completed_at' => 'Completed at',
        'cancelled_at' => 'Cancelled at',
        'rejection_reason' => 'Rejection reason',
    ],

    'filters' => [
        'status' => 'Status',
    ],

    'provider' => [
        'internal' => 'Internal',
        'docusign' => 'DocuSign',
    ],

    'status' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'failed' => 'Failed',
    ],

    'actions' => [
        'send' => 'Send',
        'cancel' => 'Cancel',
        'sign' => 'Sign',
        'reject' => 'Reject',
    ],

    'helper' => [
        'message_sensitive' => 'Avoid sensitive data. This message may be shown in the UI and must not contain secrets.',
    ],

    'driver' => [
        'internal_sent' => 'Internal send simulated.',
        'docusign_fake_sent' => 'Fake DocuSign send (no external call).',
    ],

    'events' => [
        'created' => 'Request created.',
        'updated' => 'Request updated.',
        'sent' => 'Request sent.',
        'signed' => 'Signature recorded.',
        'rejected' => 'Signature rejected.',
        'completed' => 'Request completed.',
        'cancelled' => 'Request cancelled.',
        'failed' => 'Request marked as failed.',
    ],

    'validation' => [
        'tenant_required' => 'Tenant is required.',
        'tenant_mismatch' => 'Record does not belong to the current tenant.',
        'signable_must_belong_to_tenant' => 'Signable must belong to the same tenant.',
        'docusign_requires_integration' => 'DocuSign requires an integration.',
        'integration_must_be_active_docusign' => 'Integration must be an active DocuSign signature integration in the same tenant.',
        'integration_tenant_mismatch' => 'Integration must belong to the same tenant.',
        'only_draft_editable' => 'Request can only be edited in draft.',
        'signers_only_in_draft' => 'Signers can only be changed in draft.',
        'invalid_status_transition' => 'Invalid status transition.',
        'at_least_one_signer' => 'At least one signer is required.',
        'request_must_be_sent' => 'Request must be sent.',
        'invalid_signer_transition' => 'Invalid signer status transition.',
        'not_allowed' => 'You are not allowed to perform this action.',
        'only_internal_can_sign_here' => 'Internal signing is only allowed for internal provider requests.',
    ],
];

