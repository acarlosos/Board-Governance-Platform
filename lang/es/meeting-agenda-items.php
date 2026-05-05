<?php

return [
    'section_main' => 'Agenda',

    'fields' => [
        'title' => 'Título',
        'description' => 'Descripción',
        'order_column' => 'Orden',
        'status' => 'Estado',
    ],

    'status' => [
        'pending' => 'Pendiente',
        'discussed' => 'Discutido',
        'postponed' => 'Pospuesto',
        'cancelled' => 'Cancelado',
    ],

    'validation' => [
        'attributes' => [
            'title' => 'título',
            'description' => 'descripción',
            'order_column' => 'orden',
            'status' => 'estado',
        ],
    ],
];

