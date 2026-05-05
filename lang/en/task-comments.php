<?php

return [
    'model_label' => 'Comment',
    'plural_label' => 'Comments',

    'fields' => [
        'user' => 'User',
        'comment' => 'Comment',
    ],

    'actions' => [
        'add' => 'Add comment',
    ],

    'validation' => [
        'comment_required' => 'Comment is required.',
    ],
];

