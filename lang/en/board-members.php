<?php

return [
    'section_main' => 'Members',

    'fields' => [
        'user' => 'User',
        'role' => 'Role',
        'status' => 'Status',
        'joined_at' => 'Joined at',
        'left_at' => 'Left at',
    ],

    'roles' => [
        'chairperson' => 'Chairperson',
        'member' => 'Member',
        'secretary' => 'Secretary',
        'observer' => 'Observer',
    ],

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'validation' => [
        'user_must_belong_to_board_tenant' => 'The user must belong to the same tenant as the board.',
        'duplicate_active_member' => 'This user is already an active member of this board.',
        'attributes' => [
            'user' => 'user',
            'role' => 'role',
            'status' => 'status',
            'joined_at' => 'joined at',
            'left_at' => 'left at',
        ],
    ],
];

