<?php

return [
    'plural_label' => 'Firmantes',

    'fields' => [
        'user' => 'Usuario',
        'name' => 'Nombre',
        'email' => 'Email',
        'status' => 'Estado',
        'signing_order' => 'Orden',
    ],

    'status' => [
        'pending' => 'Pendiente',
        'sent' => 'Enviado',
        'signed' => 'Firmado',
        'rejected' => 'Rechazado',
    ],

    'actions' => [
        'add' => 'Agregar firmante',
    ],

    'validation' => [
        'user_must_belong_to_tenant' => 'El usuario debe pertenecer al mismo tenant.',
    ],
];

