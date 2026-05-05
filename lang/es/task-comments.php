<?php

return [
    'model_label' => 'Comentario',
    'plural_label' => 'Comentarios',

    'fields' => [
        'user' => 'Usuario',
        'comment' => 'Comentario',
    ],

    'actions' => [
        'add' => 'Agregar comentario',
    ],

    'validation' => [
        'comment_required' => 'El comentario es obligatorio.',
    ],
];

