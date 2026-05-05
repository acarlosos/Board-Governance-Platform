<?php

return [
    'navigation_group' => 'Gobernanza',
    'model_label' => 'Reunión',
    'plural_label' => 'Reuniones',
    'navigation_label' => 'Reuniones',

    'section_main' => 'Datos de la reunión',
    'section_board' => 'Consejo',
    'section_dates' => 'Fechas',
    'section_video' => 'Videoconferencia',
    'section_organization' => 'Organización',

    'fields' => [
        'title' => 'Título',
        'description' => 'Descripción',
        'status' => 'Estado',
        'board' => 'Consejo',
        'tenant' => 'Tenant',
        'scheduled_at' => 'Programada para',
        'starts_at' => 'Inicio',
        'ends_at' => 'Fin',
        'video_conference_url' => 'Enlace de videoconferencia',
        'participants' => 'Participantes',
        'created_at' => 'Creado el',
    ],

    'status' => [
        'draft' => 'Borrador',
        'scheduled' => 'Programada',
        'in_progress' => 'En curso',
        'completed' => 'Completada',
        'cancelled' => 'Cancelada',
    ],

    'filters' => [
        'tenant' => 'Tenant',
        'board' => 'Consejo',
        'status' => 'Estado',
        'period' => 'Período',
        'from' => 'Desde',
        'until' => 'Hasta',
    ],

    'actions' => [
        'start' => 'Iniciar',
        'complete' => 'Concluir',
        'cancel' => 'Cancelar',
    ],

    'validation' => [
        'tenant_required' => 'El tenant es obligatorio.',
        'tenant_mismatch' => 'El tenant seleccionado no coincide con su contexto.',
        'board_must_belong_to_tenant' => 'El consejo debe pertenecer al mismo tenant que la reunión.',
        'invalid_status_transition' => 'Transición de estado inválida.',
        'attributes' => [
            'tenant' => 'tenant',
            'board' => 'consejo',
            'title' => 'título',
            'description' => 'descripción',
            'scheduled_at' => 'fecha programada',
            'starts_at' => 'inicio',
            'ends_at' => 'fin',
            'video_conference_url' => 'enlace de videoconferencia',
            'status' => 'estado',
        ],
    ],
];

