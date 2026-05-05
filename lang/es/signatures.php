<?php

return [
    'navigation_group' => 'Admin',
    'navigation_label' => 'Firmas',
    'model_label' => 'Solicitud de firma',
    'plural_label' => 'Solicitudes de firma',

    'sections' => [
        'data' => 'Datos',
        'signable' => 'Documento/Acta',
        'organization' => 'Organización',
        'timestamps' => 'Fechas',
    ],

    'fields' => [
        'title' => 'Título',
        'message' => 'Mensaje',
        'provider' => 'Proveedor',
        'integration' => 'Integración',
        'status' => 'Estado',
        'signable' => 'Firmable',
        'signable_type' => 'Tipo',
        'signable_id' => 'ID',
        'requested_by' => 'Solicitado por',
        'requested_at' => 'Solicitado en',
        'completed_at' => 'Completado en',
        'cancelled_at' => 'Cancelado en',
        'rejection_reason' => 'Motivo del rechazo',
    ],

    'filters' => [
        'status' => 'Estado',
    ],

    'provider' => [
        'internal' => 'Interno',
        'docusign' => 'DocuSign',
    ],

    'status' => [
        'draft' => 'Borrador',
        'sent' => 'Enviado',
        'completed' => 'Completado',
        'cancelled' => 'Cancelado',
        'failed' => 'Falló',
    ],

    'actions' => [
        'send' => 'Enviar',
        'cancel' => 'Cancelar',
        'sign' => 'Firmar',
        'reject' => 'Rechazar',
    ],

    'helper' => [
        'message_sensitive' => 'Evita datos sensibles. Este mensaje puede mostrarse en la UI y no debe contener secretos.',
    ],

    'driver' => [
        'internal_sent' => 'Envío interno simulado.',
        'docusign_fake_sent' => 'Envío fake a DocuSign (sin llamada externa).',
    ],

    'events' => [
        'created' => 'Solicitud creada.',
        'updated' => 'Solicitud actualizada.',
        'sent' => 'Solicitud enviada.',
        'signed' => 'Firma registrada.',
        'rejected' => 'Firma rechazada.',
        'completed' => 'Solicitud completada.',
        'cancelled' => 'Solicitud cancelada.',
        'failed' => 'Solicitud marcada como fallida.',
    ],

    'validation' => [
        'tenant_required' => 'El tenant es obligatorio.',
        'tenant_mismatch' => 'El registro no pertenece al tenant actual.',
        'signable_must_belong_to_tenant' => 'El firmable debe pertenecer al mismo tenant.',
        'docusign_requires_integration' => 'DocuSign requiere una integración.',
        'integration_must_be_active_docusign' => 'La integración debe ser DocuSign activa de firma en el mismo tenant.',
        'integration_tenant_mismatch' => 'La integración debe pertenecer al mismo tenant.',
        'only_draft_editable' => 'La solicitud solo se puede editar en borrador.',
        'signers_only_in_draft' => 'Los firmantes solo se pueden cambiar en borrador.',
        'invalid_status_transition' => 'Transición de estado inválida.',
        'at_least_one_signer' => 'Se requiere al menos un firmante.',
        'request_must_be_sent' => 'La solicitud debe estar enviada.',
        'invalid_signer_transition' => 'Transición de estado del firmante inválida.',
        'not_allowed' => 'No tienes permiso para ejecutar esta acción.',
        'only_internal_can_sign_here' => 'La firma interna solo está permitida para solicitudes del proveedor interno.',
    ],
];

