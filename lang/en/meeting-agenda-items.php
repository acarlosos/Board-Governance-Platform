<?php

return [
    'section_main' => 'Agenda',

    'fields' => [
        'title' => 'Title',
        'description' => 'Description',
        'order_column' => 'Order',
        'status' => 'Status',
    ],

    'status' => [
        'pending' => 'Pending',
        'discussed' => 'Discussed',
        'postponed' => 'Postponed',
        'cancelled' => 'Cancelled',
    ],

    'validation' => [
        'attributes' => [
            'title' => 'title',
            'description' => 'description',
            'order_column' => 'order',
            'status' => 'status',
        ],
    ],
];

