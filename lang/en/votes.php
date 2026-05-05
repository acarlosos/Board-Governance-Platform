<?php

return [
    'navigation_group' => 'Governance',
    'navigation_label' => 'Votes',
    'model_label' => 'Vote',
    'plural_label' => 'Votes',

    'sections' => [
        'data' => 'Details',
        'meeting' => 'Meeting',
        'configuration' => 'Configuration',
        'organization' => 'Organization',
    ],

    'fields' => [
        'title' => 'Title',
        'description' => 'Description',
        'meeting' => 'Meeting',
        'type' => 'Type',
        'status' => 'Status',
        'quorum_required' => 'Quorum (minimum votes)',
        'starts_at' => 'Starts at',
        'ends_at' => 'Ends at',
        'responses' => 'Responses',
    ],

    'types' => [
        'open' => 'Open',
        'secret' => 'Secret',
    ],

    'status' => [
        'draft' => 'Draft',
        'open' => 'Open',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
    ],

    'filters' => [
        'status' => 'Status',
    ],

    'actions' => [
        'open' => 'Open',
        'close' => 'Close',
        'cancel' => 'Cancel',
        'vote' => 'Vote',
    ],

    'validation' => [
        'tenant_required' => 'Tenant is required.',
        'tenant_mismatch' => 'You cannot access resources from another tenant.',
        'meeting_must_belong_to_tenant' => 'Meeting must belong to the same tenant.',
        'edit_only_in_draft' => 'Vote can only be edited in draft.',
        'options_only_in_draft' => 'Options can only be managed in draft.',
        'invalid_status_transition' => 'Invalid status transition.',
        'open_requires_two_options' => 'At least 2 options are required to open the vote.',
        'only_participants_can_vote' => 'Only meeting participants can vote.',
        'already_voted' => 'You have already voted in this vote.',
        'vote_not_open' => 'Vote is not open.',
        'outside_voting_period' => 'You are outside the voting period.',
        'option_must_belong_to_vote' => 'Option must belong to the vote.',
    ],
];

