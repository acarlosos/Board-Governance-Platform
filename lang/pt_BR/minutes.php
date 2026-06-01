<?php

return [
    'navigation_group' => 'Governança',
    'navigation_label' => 'Atas',
    'model_label' => 'Ata',
    'plural_label' => 'Atas',

    'sections' => [
        'data' => 'Dados',
        'content' => 'Conteúdo',
    ],

    'fields' => [
        'title' => 'Título',
        'meeting' => 'Reunião',
        'content' => 'Conteúdo',
        'status' => 'Status',
        'current_version' => 'Versão atual',
    ],

    'filters' => [
        'status' => 'Status',
    ],

    'actions' => [
        'submit_for_review' => 'Enviar para revisão',
        'approve' => 'Aprovar',
        'reject' => 'Rejeitar',
        'archive' => 'Arquivar',
        'reopen' => 'Reabrir para edição',
    ],

    'notifications' => [
        'submitted_for_review' => 'Ata enviada para revisão.',
        'approved' => 'Aprovação registada.',
        'rejected' => 'Rejeição registada.',
        'reopened' => 'Ata reaberta para edição.',
        'archived' => 'Ata arquivada.',
    ],

    'status' => [
        'draft' => 'Rascunho',
        'in_review' => 'Em revisão',
        'approved' => 'Aprovada',
        'rejected' => 'Rejeitada',
        'archived' => 'Arquivada',
    ],

    'validation' => [
        'tenant_required' => 'Tenant é obrigatório.',
        'tenant_mismatch' => 'Você não pode acessar recursos de outro tenant.',
        'meeting_must_belong_to_tenant' => 'A reunião deve pertencer ao mesmo tenant.',
        'edit_only_in_draft' => 'A ata só pode ser editada em rascunho.',
        'version_only_in_draft' => 'Versões só podem ser criadas em rascunho.',
        'submit_only_in_draft' => 'Só é possível enviar para revisão a partir de rascunho.',
        'approve_only_in_review' => 'Só é possível aprovar quando a ata está em revisão.',
        'reject_only_in_review' => 'Só é possível rejeitar quando a ata está em revisão.',
        'reopen_only_in_rejected' => 'Só é possível reabrir quando a ata foi rejeitada.',
        'archive_only_in_approved' => 'Só é possível arquivar quando a ata está aprovada.',
        'already_approved' => 'Você já aprovou esta ata.',
        'already_rejected' => 'Você já rejeitou esta ata.',
        'invalid_status_transition' => 'Transição de status inválida.',
        'no_participants_for_review' => 'A reunião não possui participantes elegíveis para revisão.',
        'not_eligible_to_approve' => 'Você não está elegível para aprovar esta ata.',
        'content_required' => 'Conteúdo é obrigatório.',
    ],
];
