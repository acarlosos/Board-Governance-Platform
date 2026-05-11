<?php

return [
    'page' => [
        'title' => 'Control panel',
        'subtitle' => 'Operational overview of governance activity',
        'nav_label' => 'Dashboard',
    ],

    'widgets' => [
        'period_caption' => 'Based on the current month (values use a short-lived cache).',

        'tasks' => [
            'heading' => 'Action items',
            'total' => 'Total tasks',
            'open' => 'Open',
            'completed' => 'Completed',
            'overdue' => 'Overdue',
        ],
        'meetings' => [
            'heading' => 'Meetings',
            'total' => 'Total meetings',
            'this_month' => 'Scheduled this month',
            'completed' => 'Completed',
        ],
        'minutes' => [
            'heading' => 'Minutes',
            'total' => 'Total minutes',
            'pending_review' => 'Pending review',
            'approved' => 'Approved',
        ],
        'votes' => [
            'heading' => 'Votes',
            'total' => 'Total votes',
            'open' => 'Open',
            'closed' => 'Closed',
        ],
        'signatures' => [
            'heading' => 'Signatures',
            'total' => 'Requests',
            'pending' => 'Pending',
            'completed' => 'Completed',
        ],
        'notifications' => [
            'heading' => 'Notifications',
            'total' => 'Total',
            'unread' => 'Unread',
        ],
    ],

    // Phase 19A.7 — Executive Dashboard. Coexists with 'widgets' (legacy) until 19B.5.
    'executive' => [
        'hero' => [
            'heading' => 'Executive overview',
            'updated_at' => 'Updated at :time',
            'period_label' => 'Period',
            'period' => [
                'this_month' => 'This month',
                'last_30_days' => 'Last 30 days',
                'all_time' => 'All time',
            ],
            'tasks_overdue' => 'Overdue tasks',
            'votes_open' => 'Open votes',
            'signatures_pending' => 'Pending signatures',
            'next_meeting_at' => 'Next meeting: :date',
        ],
        'kpis' => [
            'heading' => 'Key indicators',
            'empty' => 'No indicators for the selected period.',
            'tasks' => [
                'heading' => 'Tasks',
                'total_tasks' => 'Total',
                'tasks_open' => 'Open',
                'tasks_completed' => 'Completed',
                'tasks_overdue' => 'Overdue',
            ],
            'meetings' => [
                'heading' => 'Meetings',
                'total_meetings' => 'Total',
                'meetings_this_month' => 'This month',
                'meetings_completed' => 'Completed',
            ],
            'votes' => [
                'heading' => 'Votes',
                'total_votes' => 'Total',
                'votes_open' => 'Open',
                'votes_closed' => 'Closed',
            ],
            'signatures' => [
                'heading' => 'Signatures',
                'total_signature_requests' => 'Requests',
                'signatures_pending' => 'Pending',
                'signatures_completed' => 'Completed',
            ],
        ],
        'operations' => [
            'heading' => 'Operations',
            'empty' => 'No operational data.',
            'minutes_pending_review' => 'Minutes pending review',
            'meetings_this_month' => 'Meetings this month',
            'notifications_unread' => 'Unread notifications',
            'cta_reports' => 'View operational reports →',
        ],
        'priorities' => [
            'heading' => 'Priorities',
            'empty' => 'No active priorities for your profile.',
            'urgency' => [
                'overdue' => 'Overdue',
                'due_today' => 'Today',
                'due_this_week' => 'This week',
                'normal' => 'Normal',
            ],
            'resource' => [
                'task' => 'Task',
                'signature_signer' => 'Signature',
                'vote' => 'Vote',
            ],
        ],
        'activity' => [
            'heading' => 'Recent activity',
            'empty' => 'No activity visible to your profile.',
        ],
    ],
];
