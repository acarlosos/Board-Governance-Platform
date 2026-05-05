<?php

return [
    'navigation_group' => 'Governança',
    'navigation_label' => 'Votações',
    'model_label' => 'Votação',
    'plural_label' => 'Votações',

    'sections' => [
        'data' => 'Dados',
        'meeting' => 'Reunião',
        'configuration' => 'Configuração',
        'organization' => 'Organização',
    ],

    'fields' => [
        'title' => 'Título',
        'description' => 'Descrição',
        'meeting' => 'Reunião',
        'type' => 'Tipo',
        'status' => 'Status',
        'quorum_required' => 'Quórum (mínimo de votos)',
        'starts_at' => 'Início',
        'ends_at' => 'Fim',
        'responses' => 'Respostas',
    ],

    'types' => [
        'open' => 'Aberta',
        'secret' => 'Secreta',
    ],

    'status' => [
        'draft' => 'Rascunho',
        'open' => 'Aberta',
        'closed' => 'Fechada',
        'cancelled' => 'Cancelada',
    ],

    'filters' => [
        'status' => 'Status',
    ],

    'actions' => [
        'open' => 'Abrir',
        'close' => 'Fechar',
        'cancel' => 'Cancelar',
        'vote' => 'Votar',
    ],

    'validation' => [
        'tenant_required' => 'Tenant é obrigatório.',
        'tenant_mismatch' => 'Você não pode acessar recursos de outro tenant.',
        'meeting_must_belong_to_tenant' => 'A reunião deve pertencer ao mesmo tenant.',
        'edit_only_in_draft' => 'A votação só pode ser editada em rascunho.',
        'options_only_in_draft' => 'As opções só podem ser geridas em rascunho.',
        'invalid_status_transition' => 'Transição de status inválida.',
        'open_requires_two_options' => 'Para abrir a votação, são necessárias pelo menos 2 opções.',
        'only_participants_can_vote' => 'Apenas participantes da reunião podem votar.',
        'already_voted' => 'Você já votou nesta votação.',
        'vote_not_open' => 'A votação não está aberta.',
        'outside_voting_period' => 'Você está fora do período de votação.',
        'option_must_belong_to_vote' => 'A opção deve pertencer à votação.',
    ],
];

