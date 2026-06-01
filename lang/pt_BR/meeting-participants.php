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

    'messages' => [
        'no_users_available' => 'Não há usuários disponíveis para convidar a esta reunião.',
        'no_users_search_results' => 'Nenhum usuário encontrado para esta pesquisa.',
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
