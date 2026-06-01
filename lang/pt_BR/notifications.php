<?php

return [
    'navigation_group' => 'Admin',
    'navigation_label' => 'Notificações',
    'model_label' => 'Notificação',
    'plural_label' => 'Notificações',

    'fields' => [
        'title' => 'Título',
        'user' => 'Usuário',
        'channel' => 'Canal',
        'status' => 'Status',
        'read_at' => 'Lida em',
        'sent_at' => 'Enviada em',
        'created_at' => 'Criada em',
    ],

    'filters' => [
        'status' => 'Status',
    ],

    'actions' => [
        'mark_read' => 'Marcar como lida',
        'resend_fake' => 'Reenviar (fake)',
    ],

    'channel' => [
        'database' => 'Interna',
        'email' => 'E-mail',
    ],

    'status' => [
        'unread' => 'Não lida',
        'read' => 'Lida',
        'sent' => 'Enviada',
        'failed' => 'Falhou',
    ],

    'bell' => [
        'heading' => 'Notificações',
        'mark_all_read' => 'Marcar todas como lidas',
        'empty' => [
            'heading' => 'Sem notificações',
            'description' => 'Quando houver avisos para si, aparecerão aqui.',
        ],
    ],

    'driver' => [
        'database_ok' => 'Notificação interna registrada.',
        'email_fake_ok' => 'Envio fake de e-mail (sem SMTP).',
    ],

    'validation' => [
        'tenant_required' => 'Tenant é obrigatório.',
        'tenant_mismatch' => 'Registro não pertence ao tenant atual.',
        'user_must_belong_to_tenant' => 'O usuário deve pertencer ao mesmo tenant.',
        'related_must_belong_to_tenant' => 'O relacionado deve pertencer ao mesmo tenant.',
        'not_allowed' => 'Você não tem permissão para executar esta ação.',
    ],
];
