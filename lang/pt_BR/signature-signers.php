<?php

return [
    'plural_label' => 'Signatários',

    'fields' => [
        'user' => 'Usuário',
        'name' => 'Nome',
        'email' => 'E-mail',
        'status' => 'Status',
        'signing_order' => 'Ordem',
    ],

    'status' => [
        'pending' => 'Pendente',
        'sent' => 'Enviado',
        'signed' => 'Assinado',
        'rejected' => 'Rejeitado',
    ],

    'actions' => [
        'add' => 'Adicionar signatário',
    ],

    'validation' => [
        'user_must_belong_to_tenant' => 'O usuário deve pertencer ao mesmo tenant.',
    ],
];

