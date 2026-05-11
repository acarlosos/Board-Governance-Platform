<?php

return [
    'page' => [
        'title' => 'Panel de control',
        'subtitle' => 'Visión general operativa de la gobernanza',
        'nav_label' => 'Inicio',
    ],

    'widgets' => [
        'period_caption' => 'Referencia: mes en curso (valores con caché breve).',

        'tasks' => [
            'heading' => 'Tareas pendientes',
            'total' => 'Total de tareas',
            'open' => 'Abiertas',
            'completed' => 'Completadas',
            'overdue' => 'Atrasadas',
        ],
        'meetings' => [
            'heading' => 'Reuniones',
            'total' => 'Total de reuniones',
            'this_month' => 'Programadas este mes',
            'completed' => 'Completadas',
        ],
        'minutes' => [
            'heading' => 'Actas',
            'total' => 'Total de actas',
            'pending_review' => 'En revisión',
            'approved' => 'Aprobadas',
        ],
        'votes' => [
            'heading' => 'Votaciones',
            'total' => 'Total de votaciones',
            'open' => 'Abiertas',
            'closed' => 'Cerradas',
        ],
        'signatures' => [
            'heading' => 'Firmas',
            'total' => 'Solicitudes',
            'pending' => 'Pendientes',
            'completed' => 'Completadas',
        ],
        'notifications' => [
            'heading' => 'Notificaciones',
            'total' => 'Total',
            'unread' => 'Sin leer',
        ],
    ],

    // Fase 19A.7 — Executive Dashboard. Coexiste con 'widgets' (legacy) hasta 19B.5.
    'executive' => [
        'hero' => [
            'heading' => 'Vista ejecutiva',
            'updated_at' => 'Actualizado a las :time',
            'period_label' => 'Período',
            'period' => [
                'this_month' => 'Mes actual',
                'last_30_days' => 'Últimos 30 días',
                'all_time' => 'Histórico',
            ],
            'tasks_overdue' => 'Tareas atrasadas',
            'votes_open' => 'Votaciones abiertas',
            'signatures_pending' => 'Firmas pendientes',
            'next_meeting_at' => 'Próxima reunión: :date',
        ],
        'kpis' => [
            'heading' => 'Indicadores',
            'empty' => 'Sin indicadores para el período seleccionado.',
            'tasks' => [
                'heading' => 'Tareas',
                'total_tasks' => 'Total',
                'tasks_open' => 'Abiertas',
                'tasks_completed' => 'Completadas',
                'tasks_overdue' => 'Atrasadas',
            ],
            'meetings' => [
                'heading' => 'Reuniones',
                'total_meetings' => 'Total',
                'meetings_this_month' => 'Este mes',
                'meetings_completed' => 'Completadas',
            ],
            'votes' => [
                'heading' => 'Votaciones',
                'total_votes' => 'Total',
                'votes_open' => 'Abiertas',
                'votes_closed' => 'Cerradas',
            ],
            'signatures' => [
                'heading' => 'Firmas',
                'total_signature_requests' => 'Solicitudes',
                'signatures_pending' => 'Pendientes',
                'signatures_completed' => 'Completadas',
            ],
        ],
        'operations' => [
            'heading' => 'Operaciones',
            'empty' => 'Sin datos operativos.',
            'minutes_pending_review' => 'Actas en revisión',
            'meetings_this_month' => 'Reuniones este mes',
            'notifications_unread' => 'Notificaciones sin leer',
            'cta_reports' => 'Ver informes operativos →',
        ],
        'priorities' => [
            'heading' => 'Prioridades',
            'empty' => 'Sin prioridades activas para tu perfil.',
            'urgency' => [
                'overdue' => 'Atrasado',
                'due_today' => 'Hoy',
                'due_this_week' => 'Esta semana',
                'normal' => 'Normal',
            ],
            'resource' => [
                'task' => 'Tarea',
                'signature_signer' => 'Firma',
                'vote' => 'Votación',
            ],
        ],
        'activity' => [
            'heading' => 'Actividad reciente',
            'empty' => 'Sin actividad visible para tu perfil.',
        ],
    ],
];
