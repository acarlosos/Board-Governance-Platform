<?php

return [
    'section_main' => 'Participantes',

    'fields' => [
        'user' => 'Usuario',
        'role' => 'Rol',
        'status' => 'Estado',
        'responded_at' => 'Respondido el',
    ],

    'roles' => [
        'chairperson' => 'Presidente',
        'participant' => 'Participante',
        'secretary' => 'Secretario',
        'guest' => 'Invitado',
    ],

    'status' => [
        'invited' => 'Invitado',
        'confirmed' => 'Confirmado',
        'declined' => 'Rechazado',
        'absent' => 'Ausente',
    ],

    'messages' => [
        'no_users_available' => 'No hay usuarios disponibles para invitar a esta reunión.',
        'no_users_search_results' => 'Ningún usuario encontrado para esta búsqueda.',
    ],

    'validation' => [
        'user_must_belong_to_meeting_tenant' => 'El usuario debe pertenecer al mismo tenant que la reunión.',
        'duplicate_active_participant' => 'Este usuario ya es un participante activo de esta reunión.',
        'attributes' => [
            'user' => 'usuario',
            'role' => 'rol',
            'status' => 'estado',
            'responded_at' => 'respondido el',
        ],
    ],
];
