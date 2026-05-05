<?php

return [
    'navigation_group' => 'Admin',
    'navigation_label' => 'Templates de notificación',
    'model_label' => 'Template',
    'plural_label' => 'Templates',

    'sections' => [
        'data' => 'Datos',
        'content' => 'Contenido',
        'variables' => 'Variables',
        'organization' => 'Organización',
    ],

    'fields' => [
        'tenant' => 'Organización',
        'key' => 'Clave',
        'name' => 'Nombre',
        'subject' => 'Asunto',
        'body' => 'Cuerpo',
        'locale' => 'Idioma',
        'channel' => 'Canal',
        'status' => 'Estado',
        'variables' => 'Variables',
        'variable_key' => 'Variable',
        'variable_description' => 'Descripción',
        'updated_at' => 'Actualizado el',
    ],

    'filters' => [
        'status' => 'Estado',
    ],

    'status' => [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
    ],

    'helper' => [
        'global_or_tenant' => 'Los templates globales (sin organización) actúan como fallback. Los templates del tenant sobrescriben por clave/idioma/canal.',
    ],

    'validation' => [
        'tenant_required' => 'El tenant es obligatorio.',
        'tenant_mismatch' => 'El registro no pertenece al tenant actual.',
        'cannot_edit_global' => 'No puedes editar templates globales.',
    ],
];

