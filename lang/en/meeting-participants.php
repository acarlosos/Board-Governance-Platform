<?php

return [
    'section_main' => 'Participants',

    'fields' => [
        'user' => 'User',
        'role' => 'Role',
        'status' => 'Status',
        'responded_at' => 'Responded at',
    ],

    'roles' => [
        'chairperson' => 'Chairperson',
        'participant' => 'Participant',
        'secretary' => 'Secretary',
        'guest' => 'Guest',
    ],

    'status' => [
        'invited' => 'Invited',
        'confirmed' => 'Confirmed',
        'declined' => 'Declined',
        'absent' => 'Absent',
    ],

    'messages' => [
        'no_users_available' => 'No users are available to invite to this meeting.',
        'no_users_search_results' => 'No users found for this search.',
    ],

    'validation' => [
        'user_must_belong_to_meeting_tenant' => 'The user must belong to the same tenant as the meeting.',
        'duplicate_active_participant' => 'This user is already an active participant of this meeting.',
        'attributes' => [
            'user' => 'user',
            'role' => 'role',
            'status' => 'status',
            'responded_at' => 'responded at',
        ],
    ],
];
