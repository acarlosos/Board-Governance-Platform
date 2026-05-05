<?php

return [
    'navigation_group' => 'Gobernanza',
    'navigation_label' => 'Votaciones',
    'model_label' => 'Votación',
    'plural_label' => 'Votaciones',

    'sections' => [
        'data' => 'Datos',
        'meeting' => 'Reunión',
        'configuration' => 'Configuración',
        'organization' => 'Organización',
    ],

    'fields' => [
        'title' => 'Título',
        'description' => 'Descripción',
        'meeting' => 'Reunión',
        'type' => 'Tipo',
        'status' => 'Estado',
        'quorum_required' => 'Quórum (mínimo de votos)',
        'starts_at' => 'Inicio',
        'ends_at' => 'Fin',
        'responses' => 'Respuestas',
    ],

    'types' => [
        'open' => 'Abierta',
        'secret' => 'Secreta',
    ],

    'status' => [
        'draft' => 'Borrador',
        'open' => 'Abierta',
        'closed' => 'Cerrada',
        'cancelled' => 'Cancelada',
    ],

    'filters' => [
        'status' => 'Estado',
    ],

    'actions' => [
        'open' => 'Abrir',
        'close' => 'Cerrar',
        'cancel' => 'Cancelar',
        'vote' => 'Votar',
    ],

    'validation' => [
        'tenant_required' => 'El tenant es obligatorio.',
        'tenant_mismatch' => 'No puedes acceder a recursos de otro tenant.',
        'meeting_must_belong_to_tenant' => 'La reunión debe pertenecer al mismo tenant.',
        'edit_only_in_draft' => 'La votación solo puede editarse en borrador.',
        'options_only_in_draft' => 'Las opciones solo pueden gestionarse en borrador.',
        'invalid_status_transition' => 'Transición de estado inválida.',
        'open_requires_two_options' => 'Se requieren al menos 2 opciones para abrir la votación.',
        'only_participants_can_vote' => 'Solo los participantes de la reunión pueden votar.',
        'already_voted' => 'Ya has votado en esta votación.',
        'vote_not_open' => 'La votación no está abierta.',
        'outside_voting_period' => 'Estás fuera del período de votación.',
        'option_must_belong_to_vote' => 'La opción debe pertenecer a la votación.',
    ],
];

