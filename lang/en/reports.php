<?php

return [
    'navigation_label' => 'Operational reports',
    'navigation_group' => 'Reports',
    'title' => 'Operational reports',

    'fields' => [
        'period' => 'Period',
        'quantity' => 'Count',
        'month' => 'Month',
    ],

    'periods' => [
        'this_month' => 'Current month',
        'last_30_days' => 'Last 30 days',
        'all_time' => 'All time',
    ],

    'sections' => [
        'tasks_by_status' => 'Tasks by status',
        'meetings_by_month' => 'Meetings per month (last 12 months, by scheduled date)',
        'votes_by_status' => 'Votes by status',
        'signatures_by_status' => 'Signatures by status',
    ],

    'empty' => [
        'no_rows' => 'No records for this period.',
    ],
];
