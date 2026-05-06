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

    'helpers' => [
        'period' => 'Choose a period to refresh totals and breakdowns.',
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

    'meetings' => [
        'unit' => 'meetings',
    ],

    'empty' => [
        'heading' => 'No data for this period',
        'description' => 'We couldn’t find enough records to display reports for this period. Try changing the filter.',
        'no_rows' => 'No records for this period.',
    ],
];
