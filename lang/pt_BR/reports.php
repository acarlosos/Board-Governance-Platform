<?php

return [
    'navigation_label' => 'Relatórios operacionais',
    'navigation_group' => 'Relatórios',
    'title' => 'Relatórios operacionais',

    'fields' => [
        'period' => 'Período',
        'quantity' => 'Quantidade',
        'month' => 'Mês',
    ],

    'helpers' => [
        'period' => 'Selecione um período para atualizar os totais e agrupamentos.',
    ],

    'periods' => [
        'this_month' => 'Mês corrente',
        'last_30_days' => 'Últimos 30 dias',
        'all_time' => 'Todo o período',
    ],

    'sections' => [
        'tasks_by_status' => 'Tarefas por status',
        'meetings_by_month' => 'Reuniões por mês (últimos 12 meses, por data agendada)',
        'votes_by_status' => 'Votações por status',
        'signatures_by_status' => 'Assinaturas por status',
    ],

    'meetings' => [
        'unit' => 'reuniões',
    ],

    'empty' => [
        'heading' => 'Sem dados para este período',
        'description' => 'Não encontramos registros suficientes para exibir relatórios neste período. Tente alterar o filtro.',
        'no_rows' => 'Sem registros neste período.',
    ],
];
