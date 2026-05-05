<?php

return [
    'navigation_group' => 'Admin',
    'navigation_label' => 'Assinaturas',
    'model_label' => 'Solicitação de assinatura',
    'plural_label' => 'Solicitações de assinatura',

    'sections' => [
        'data' => 'Dados',
        'signable' => 'Documento/Ata',
        'organization' => 'Organização',
        'timestamps' => 'Datas',
    ],

    'fields' => [
        'title' => 'Título',
        'message' => 'Mensagem',
        'provider' => 'Provedor',
        'integration' => 'Integração',
        'status' => 'Status',
        'signable' => 'Assinável',
        'signable_type' => 'Tipo',
        'signable_id' => 'ID',
        'requested_by' => 'Solicitado por',
        'requested_at' => 'Solicitado em',
        'completed_at' => 'Concluído em',
        'cancelled_at' => 'Cancelado em',
        'rejection_reason' => 'Motivo da rejeição',
    ],

    'filters' => [
        'status' => 'Status',
    ],

    'provider' => [
        'internal' => 'Interno',
        'docusign' => 'DocuSign',
    ],

    'status' => [
        'draft' => 'Rascunho',
        'sent' => 'Enviado',
        'completed' => 'Concluído',
        'cancelled' => 'Cancelado',
        'failed' => 'Falhou',
    ],

    'actions' => [
        'send' => 'Enviar',
        'cancel' => 'Cancelar',
        'sign' => 'Assinar',
        'reject' => 'Rejeitar',
    ],

    'helper' => [
        'message_sensitive' => 'Evite inserir dados sensíveis. Esta mensagem pode ser exibida na UI e não deve conter segredos.',
    ],

    'driver' => [
        'internal_sent' => 'Envio interno simulado.',
        'docusign_fake_sent' => 'Envio fake para DocuSign (sem chamada externa).',
    ],

    'events' => [
        'created' => 'Solicitação criada.',
        'updated' => 'Solicitação atualizada.',
        'sent' => 'Solicitação enviada.',
        'signed' => 'Assinatura registrada.',
        'rejected' => 'Assinatura rejeitada.',
        'completed' => 'Solicitação concluída.',
        'cancelled' => 'Solicitação cancelada.',
        'failed' => 'Solicitação marcada como falha.',
    ],

    'validation' => [
        'tenant_required' => 'Tenant é obrigatório.',
        'tenant_mismatch' => 'Registro não pertence ao tenant atual.',
        'signable_must_belong_to_tenant' => 'O documento/ata deve pertencer ao mesmo tenant.',
        'docusign_requires_integration' => 'DocuSign exige uma integração configurada.',
        'integration_must_be_active_docusign' => 'A integração deve ser DocuSign ativa do tipo assinatura no mesmo tenant.',
        'integration_tenant_mismatch' => 'A integração deve pertencer ao mesmo tenant.',
        'only_draft_editable' => 'A solicitação só pode ser editada em rascunho.',
        'signers_only_in_draft' => 'Signatários só podem ser alterados em rascunho.',
        'invalid_status_transition' => 'Transição de status inválida.',
        'at_least_one_signer' => 'É obrigatório ter pelo menos 1 signatário.',
        'request_must_be_sent' => 'A solicitação deve estar enviada.',
        'invalid_signer_transition' => 'Transição de status do signatário inválida.',
        'not_allowed' => 'Você não tem permissão para executar esta ação.',
        'only_internal_can_sign_here' => 'Assinatura interna só é permitida para solicitações do provedor interno.',
    ],
];

