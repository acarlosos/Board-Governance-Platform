<?php

return [
    'section_main' => 'Participantes',

    'fields' => [
        'user' => 'Usuário',
        'role' => 'Papel',
        'status' => 'Estado',
        'responded_at' => 'Respondido em',
    ],

    'roles' => [
        'chairperson' => 'Presidente',
        'participant' => 'Participante',
        'secretary' => 'Secretário',
        'guest' => 'Convidado',
    ],

    'status' => [
        'invited' => 'Convidado',
        'confirmed' => 'Confirmado',
        'declined' => 'Recusado',
        'absent' => 'Ausente',
    ],

    'validation' => [
        'user_must_belong_to_meeting_tenant' => 'O usuário deve pertencer ao mesmo tenant da reunião.',
        'duplicate_active_participant' => 'Este usuário já é um participante ativo desta reunião.',
        'attributes' => [
            'user' => 'usuário',
            'role' => 'papel',
            'status' => 'estado',
            'responded_at' => 'respondido em',
        ],
    ],
];

