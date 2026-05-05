<?php

return [
    'navigation_group' => 'Workflows',
    'navigation_label' => 'Pendências',
    'model_label' => 'Pendência',
    'plural_label' => 'Pendências',

    'sections' => [
        'data' => 'Dados',
        'assignment' => 'Atribuição',
        'due' => 'Prazo e prioridade',
        'related' => 'Relacionado a',
        'status' => 'Status',
    ],

    'fields' => [
        'title' => 'Título',
        'description' => 'Descrição',
        'status' => 'Status',
        'priority' => 'Prioridade',
        'due_date' => 'Prazo',
        'assigned_to' => 'Atribuído para',
        'related_type' => 'Tipo relacionado',
        'related_id' => 'ID relacionado',
        'completed_at' => 'Concluída em',
    ],

    'filters' => [
        'status' => 'Status',
    ],

    'status' => [
        'pending' => 'Pendente',
        'in_progress' => 'Em andamento',
        'completed' => 'Concluída',
        'cancelled' => 'Cancelada',
    ],

    'priority' => [
        'low' => 'Baixa',
        'normal' => 'Normal',
        'high' => 'Alta',
        'urgent' => 'Urgente',
    ],

    'actions' => [
        'start' => 'Iniciar',
        'complete' => 'Concluir',
        'cancel' => 'Cancelar',
    ],

    'validation' => [
        'tenant_required' => 'Tenant é obrigatório.',
        'tenant_mismatch' => 'Registro não pertence ao tenant atual.',
        'assigned_must_belong_to_tenant' => 'O usuário atribuído deve pertencer ao mesmo tenant.',
        'related_must_belong_to_tenant' => 'O relacionamento deve apontar para uma entidade do mesmo tenant.',
        'invalid_status_transition' => 'Transição de status inválida.',
        'not_allowed' => 'Você não tem permissão para executar esta ação.',
    ],
];

