<?php

return [
    'navigation_label' => 'Informes operativos',
    'navigation_group' => 'Informes',
    'title' => 'Informes operativos',

    'fields' => [
        'period' => 'Período',
        'quantity' => 'Cantidad',
        'month' => 'Mes',
    ],

    'helpers' => [
        'period' => 'Seleccione un período para actualizar los totales y los agrupamientos.',
    ],

    'periods' => [
        'this_month' => 'Mes actual',
        'last_30_days' => 'Últimos 30 días',
        'all_time' => 'Todo el período',
    ],

    'sections' => [
        'tasks_by_status' => 'Tareas por estado',
        'meetings_by_month' => 'Reuniones por mes (últimos 12 meses, por fecha programada)',
        'votes_by_status' => 'Votaciones por estado',
        'signatures_by_status' => 'Firmas por estado',
    ],

    'meetings' => [
        'unit' => 'reuniones',
    ],

    'empty' => [
        'heading' => 'Sin datos para este período',
        'description' => 'No encontramos registros suficientes para mostrar informes en este período. Pruebe cambiar el filtro.',
        'no_rows' => 'Sin registros en este período.',
    ],
];
