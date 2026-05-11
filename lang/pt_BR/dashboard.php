<?php

return [
    'page' => [
        'title' => 'Painel de Controle',
        'subtitle' => 'Visão geral operacional da governança',
        'nav_label' => 'Painel',
    ],

    'widgets' => [
        'period_caption' => 'Referência: mês corrente (valores com cache curto).',

        'tasks' => [
            'heading' => 'Pendências',
            'total' => 'Total de tarefas',
            'open' => 'Em aberto',
            'completed' => 'Concluídas',
            'overdue' => 'Em atraso',
        ],
        'meetings' => [
            'heading' => 'Reuniões',
            'total' => 'Total de reuniões',
            'this_month' => 'Agendadas neste mês',
            'completed' => 'Concluídas',
        ],
        'minutes' => [
            'heading' => 'Atas',
            'total' => 'Total de atas',
            'pending_review' => 'Em revisão',
            'approved' => 'Aprovadas',
        ],
        'votes' => [
            'heading' => 'Votações',
            'total' => 'Total de votações',
            'open' => 'Abertas',
            'closed' => 'Encerradas',
        ],
        'signatures' => [
            'heading' => 'Assinaturas',
            'total' => 'Solicitações',
            'pending' => 'Pendentes',
            'completed' => 'Concluídas',
        ],
        'notifications' => [
            'heading' => 'Notificações',
            'total' => 'Total',
            'unread' => 'Não lidas',
        ],
    ],

    // Fase 19A.7 — Executive Dashboard. Coexiste com 'widgets' (legados) até 19B.5.
    'executive' => [
        'hero' => [
            'heading' => 'Visão executiva',
            'updated_at' => 'Atualizado às :time',
            'period_label' => 'Período',
            'period' => [
                'this_month' => 'Mês corrente',
                'last_30_days' => 'Últimos 30 dias',
                'all_time' => 'Histórico',
            ],
            'tasks_overdue' => 'Tarefas em atraso',
            'votes_open' => 'Votações abertas',
            'signatures_pending' => 'Assinaturas pendentes',
            'next_meeting_at' => 'Próxima reunião: :date',
        ],
        'kpis' => [
            'heading' => 'Indicadores',
            'empty' => 'Sem indicadores para o período seleccionado.',
            'tasks' => [
                'heading' => 'Tarefas',
                'total_tasks' => 'Total',
                'tasks_open' => 'Em aberto',
                'tasks_completed' => 'Concluídas',
                'tasks_overdue' => 'Em atraso',
            ],
            'meetings' => [
                'heading' => 'Reuniões',
                'total_meetings' => 'Total',
                'meetings_this_month' => 'Este mês',
                'meetings_completed' => 'Concluídas',
            ],
            'votes' => [
                'heading' => 'Votações',
                'total_votes' => 'Total',
                'votes_open' => 'Abertas',
                'votes_closed' => 'Encerradas',
            ],
            'signatures' => [
                'heading' => 'Assinaturas',
                'total_signature_requests' => 'Solicitações',
                'signatures_pending' => 'Pendentes',
                'signatures_completed' => 'Concluídas',
            ],
        ],
        'operations' => [
            'heading' => 'Operações',
            'empty' => 'Sem dados operacionais.',
            'minutes_pending_review' => 'Atas em revisão',
            'meetings_this_month' => 'Reuniões neste mês',
            'notifications_unread' => 'Notificações não lidas',
            'cta_reports' => 'Ver relatórios operacionais →',
        ],
        'priorities' => [
            'heading' => 'Prioridades',
            'empty' => 'Sem prioridades activas para o seu perfil.',
            'urgency' => [
                'overdue' => 'Em atraso',
                'due_today' => 'Hoje',
                'due_this_week' => 'Esta semana',
                'normal' => 'Normal',
            ],
            'resource' => [
                'task' => 'Tarefa',
                'signature_signer' => 'Assinatura',
                'vote' => 'Votação',
            ],
        ],
        'activity' => [
            'heading' => 'Atividade recente',
            'empty' => 'Sem atividade visível para o seu perfil.',
        ],
    ],
];
