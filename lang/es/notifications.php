<?php

return [
    'navigation_group' => 'Admin',
    'navigation_label' => 'Notificaciones',
    'model_label' => 'Notificación',
    'plural_label' => 'Notificaciones',

    'fields' => [
        'title' => 'Título',
        'user' => 'Usuario',
        'channel' => 'Canal',
        'status' => 'Estado',
        'read_at' => 'Leída el',
        'sent_at' => 'Enviada el',
        'created_at' => 'Creada el',
    ],

    'filters' => [
        'status' => 'Estado',
    ],

    'actions' => [
        'mark_read' => 'Marcar como leída',
        'resend_fake' => 'Reenviar (fake)',
    ],

    'channel' => [
        'database' => 'Interna',
        'email' => 'Email',
    ],

    'status' => [
        'unread' => 'No leída',
        'read' => 'Leída',
        'sent' => 'Enviada',
        'failed' => 'Falló',
    ],

    'bell' => [
        'heading' => 'Notificaciones',
        'mark_all_read' => 'Marcar todas como leídas',
        'empty' => [
            'heading' => 'Sin notificaciones',
            'description' => 'Cuando tengas avisos, aparecerán aquí.',
        ],
    ],

    'driver' => [
        'database_ok' => 'Notificación interna registrada.',
        'email_fake_ok' => 'Envío fake de email (sin SMTP).',
    ],

    'validation' => [
        'tenant_required' => 'El tenant es obligatorio.',
        'tenant_mismatch' => 'El registro no pertenece al tenant actual.',
        'user_must_belong_to_tenant' => 'El usuario debe pertenecer al mismo tenant.',
        'related_must_belong_to_tenant' => 'El relacionado debe pertenecer al mismo tenant.',
        'not_allowed' => 'No tienes permiso para ejecutar esta acción.',
    ],
];
