<?php

return [
    'plural_label' => 'Signers',

    'fields' => [
        'user' => 'User',
        'name' => 'Name',
        'email' => 'Email',
        'status' => 'Status',
        'signing_order' => 'Order',
    ],

    'status' => [
        'pending' => 'Pending',
        'sent' => 'Sent',
        'signed' => 'Signed',
        'rejected' => 'Rejected',
    ],

    'actions' => [
        'add' => 'Add signer',
    ],

    'validation' => [
        'user_must_belong_to_tenant' => 'User must belong to the same tenant.',
    ],
];

