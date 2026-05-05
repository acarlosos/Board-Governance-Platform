<?php

return [
    'section_main' => 'Miembros',

    'fields' => [
        'user' => 'Usuario',
        'role' => 'Rol',
        'status' => 'Estado',
        'joined_at' => 'Se unió el',
        'left_at' => 'Salió el',
    ],

    'roles' => [
        'chairperson' => 'Presidente',
        'member' => 'Miembro',
        'secretary' => 'Secretario',
        'observer' => 'Observador',
    ],

    'status' => [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
    ],

    'validation' => [
        'user_must_belong_to_board_tenant' => 'El usuario debe pertenecer al mismo tenant que el consejo.',
        'duplicate_active_member' => 'Este usuario ya es un miembro activo de este consejo.',
        'attributes' => [
            'user' => 'usuario',
            'role' => 'rol',
            'status' => 'estado',
            'joined_at' => 'fecha de entrada',
            'left_at' => 'fecha de salida',
        ],
    ],
];

