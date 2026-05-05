<?php

return [
    'section_main' => 'Membros',

    'fields' => [
        'user' => 'Utilizador',
        'role' => 'Papel',
        'status' => 'Estado',
        'joined_at' => 'Entrou em',
        'left_at' => 'Saiu em',
    ],

    'roles' => [
        'chairperson' => 'Presidente',
        'member' => 'Membro',
        'secretary' => 'Secretário',
        'observer' => 'Observador',
    ],

    'status' => [
        'active' => 'Ativo',
        'inactive' => 'Inativo',
    ],

    'validation' => [
        'user_must_belong_to_board_tenant' => 'O utilizador deve pertencer ao mesmo tenant do conselho.',
        'duplicate_active_member' => 'Este utilizador já é um membro ativo deste conselho.',
        'attributes' => [
            'user' => 'utilizador',
            'role' => 'papel',
            'status' => 'estado',
            'joined_at' => 'data de entrada',
            'left_at' => 'data de saída',
        ],
    ],
];

