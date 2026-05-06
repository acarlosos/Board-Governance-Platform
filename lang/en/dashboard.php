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
];
