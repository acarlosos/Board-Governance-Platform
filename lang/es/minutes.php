<?php

return [
    'navigation_group' => 'Gobernanza',
    'navigation_label' => 'Actas',
    'model_label' => 'Acta',
    'plural_label' => 'Actas',

    'sections' => [
        'data' => 'Datos',
        'content' => 'Contenido',
    ],

    'fields' => [
        'title' => 'Título',
        'meeting' => 'Reunión',
        'content' => 'Contenido',
        'status' => 'Estado',
        'current_version' => 'Versión actual',
    ],

    'filters' => [
        'status' => 'Estado',
    ],

    'actions' => [
        'submit_for_review' => 'Enviar para revisión',
        'approve' => 'Aprobar',
        'reject' => 'Rechazar',
        'archive' => 'Archivar',
        'reopen' => 'Reabrir para edición',
    ],

    'status' => [
        'draft' => 'Borrador',
        'in_review' => 'En revisión',
        'approved' => 'Aprobada',
        'rejected' => 'Rechazada',
        'archived' => 'Archivada',
    ],

    'validation' => [
        'tenant_required' => 'El tenant es obligatorio.',
        'tenant_mismatch' => 'No puedes acceder a recursos de otro tenant.',
        'meeting_must_belong_to_tenant' => 'La reunión debe pertenecer al mismo tenant.',
        'edit_only_in_draft' => 'El acta solo puede editarse en borrador.',
        'version_only_in_draft' => 'Las versiones solo pueden crearse en borrador.',
        'submit_only_in_draft' => 'Solo se puede enviar a revisión desde borrador.',
        'approve_only_in_review' => 'Solo se puede aprobar cuando está en revisión.',
        'reject_only_in_review' => 'Solo se puede rechazar cuando está en revisión.',
        'reopen_only_in_rejected' => 'Solo se puede reabrir después de rechazo.',
        'archive_only_in_approved' => 'Solo se puede archivar después de la aprobación.',
        'already_approved' => 'Ya has aprobado esta acta.',
        'already_rejected' => 'Ya has rechazado esta acta.',
        'invalid_status_transition' => 'Transición de estado inválida.',
        'no_participants_for_review' => 'La reunión no tiene participantes elegibles para revisión.',
        'not_eligible_to_approve' => 'No eres elegible para aprobar este acta.',
        'content_required' => 'El contenido es obligatorio.',
    ],
];

