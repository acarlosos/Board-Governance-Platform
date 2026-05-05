<?php

return [
    'section_main' => 'Pauta',

    'fields' => [
        'title' => 'Título',
        'description' => 'Descrição',
        'order_column' => 'Ordem',
        'status' => 'Estado',
    ],

    'status' => [
        'pending' => 'Pendente',
        'discussed' => 'Discutido',
        'postponed' => 'Adiado',
        'cancelled' => 'Cancelado',
    ],

    'validation' => [
        'attributes' => [
            'title' => 'título',
            'description' => 'descrição',
            'order_column' => 'ordem',
            'status' => 'estado',
        ],
    ],
];

