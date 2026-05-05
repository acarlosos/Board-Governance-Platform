<?php

return [
    'navigation_group' => 'Governança',
    'model_label' => 'Reunião',
    'plural_label' => 'Reuniões',
    'navigation_label' => 'Reuniões',

    'section_main' => 'Dados da reunião',
    'section_board' => 'Conselho',
    'section_dates' => 'Datas',
    'section_video' => 'Videoconferência',
    'section_organization' => 'Organização',

    'fields' => [
        'title' => 'Título',
        'description' => 'Descrição',
        'status' => 'Estado',
        'board' => 'Conselho',
        'tenant' => 'Tenant',
        'scheduled_at' => 'Agendada para',
        'starts_at' => 'Início',
        'ends_at' => 'Fim',
        'video_conference_url' => 'Link de videoconferência',
        'participants' => 'Participantes',
        'created_at' => 'Criado em',
    ],

    'status' => [
        'draft' => 'Rascunho',
        'scheduled' => 'Agendada',
        'in_progress' => 'Em andamento',
        'completed' => 'Concluída',
        'cancelled' => 'Cancelada',
    ],

    'filters' => [
        'tenant' => 'Tenant',
        'board' => 'Conselho',
        'status' => 'Estado',
        'period' => 'Período',
        'from' => 'De',
        'until' => 'Até',
    ],

    'actions' => [
        'start' => 'Iniciar',
        'complete' => 'Concluir',
        'cancel' => 'Cancelar',
    ],

    'validation' => [
        'tenant_required' => 'O tenant é obrigatório.',
        'tenant_mismatch' => 'O tenant informado não corresponde ao seu contexto.',
        'board_must_belong_to_tenant' => 'O conselho deve pertencer ao mesmo tenant da reunião.',
        'invalid_status_transition' => 'Transição de estado inválida.',
        'attributes' => [
            'tenant' => 'tenant',
            'board' => 'conselho',
            'title' => 'título',
            'description' => 'descrição',
            'scheduled_at' => 'data agendada',
            'starts_at' => 'início',
            'ends_at' => 'fim',
            'video_conference_url' => 'link de videoconferência',
            'status' => 'estado',
        ],
    ],
];

