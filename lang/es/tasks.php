<?php

return [
    'navigation_group' => 'Workflows',
    'navigation_label' => 'Tareas',
    'model_label' => 'Tarea',
    'plural_label' => 'Tareas',

    'sections' => [
        'data' => 'Datos',
        'assignment' => 'Asignación',
        'due' => 'Plazo y prioridad',
        'related' => 'Relacionado con',
        'status' => 'Estado',
    ],

    'fields' => [
        'title' => 'Título',
        'description' => 'Descripción',
        'status' => 'Estado',
        'priority' => 'Prioridad',
        'due_date' => 'Plazo',
        'assigned_to' => 'Asignado a',
        'related_type' => 'Tipo relacionado',
        'related_id' => 'ID relacionado',
        'completed_at' => 'Completada en',
    ],

    'filters' => [
        'status' => 'Estado',
    ],

    'status' => [
        'pending' => 'Pendiente',
        'in_progress' => 'En progreso',
        'completed' => 'Completada',
        'cancelled' => 'Cancelada',
    ],

    'priority' => [
        'low' => 'Baja',
        'normal' => 'Normal',
        'high' => 'Alta',
        'urgent' => 'Urgente',
    ],

    'actions' => [
        'start' => 'Iniciar',
        'complete' => 'Completar',
        'cancel' => 'Cancelar',
    ],

    'validation' => [
        'tenant_required' => 'El tenant es obligatorio.',
        'tenant_mismatch' => 'El registro no pertenece al tenant actual.',
        'assigned_must_belong_to_tenant' => 'El usuario asignado debe pertenecer al mismo tenant.',
        'related_must_belong_to_tenant' => 'La relación debe apuntar a una entidad del mismo tenant.',
        'invalid_status_transition' => 'Transición de estado inválida.',
        'not_allowed' => 'No tienes permiso para ejecutar esta acción.',
    ],
];

