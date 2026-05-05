<?php

return [
    'navigation_group' => 'Admin',
    'navigation_label' => 'Integraciones',
    'model_label' => 'Integración',
    'plural_label' => 'Integraciones',

    'sections' => [
        'data' => 'Datos',
        'config' => 'Configuración',
        'test' => 'Prueba',
        'organization' => 'Organización',
    ],

    'fields' => [
        'tenant' => 'Organización',
        'created_at' => 'Creado el',
        'updated_at' => 'Actualizado el',
        'name' => 'Nombre',
        'type' => 'Tipo',
        'provider' => 'Proveedor',
        'status' => 'Estado',
        'last_tested_at' => 'Última prueba',
        'last_test_status' => 'Estado de la prueba',
        'last_test_message' => 'Mensaje de la prueba',
    ],

    'filters' => [
        'status' => 'Estado',
    ],

    'type' => [
        'email' => 'Email',
        'storage' => 'Storage',
        'signature' => 'Firma',
        'video_conference' => 'Videoconferencia',
        'reporting' => 'Reportes',
        'identity' => 'Identidad',
    ],

    'provider' => [
        'smtp' => 'SMTP',
        'microsoft_365' => 'Microsoft 365',
        'onedrive' => 'OneDrive',
        'docusign' => 'DocuSign',
        'teams' => 'Teams',
        'zoom' => 'Zoom',
        'looker_studio' => 'Looker Studio',
    ],

    'status' => [
        'inactive' => 'Inactiva',
        'active' => 'Activa',
        'error' => 'Error',
    ],

    'actions' => [
        'test' => 'Probar conexión',
        'enable' => 'Activar',
        'disable' => 'Desactivar',
    ],

    'logs' => [
        'created' => 'Integración creada.',
        'updated' => 'Integración actualizada.',
        'enabled' => 'Integración activada.',
        'disabled' => 'Integración desactivada.',
    ],

    'helper' => [
        'keep_secret_if_empty' => 'Deja en blanco para mantener el valor actual.',
    ],

    'validation' => [
        'tenant_required' => 'El tenant es obligatorio.',
        'tenant_mismatch' => 'El registro no pertenece al tenant actual.',
        'missing_required' => 'Faltan campos obligatorios',
        'enable_requires_successful_test' => 'Solo se puede activar después de una prueba exitosa.',
    ],

    'test' => [
        'ok' => 'La configuración es válida (prueba fake).',
        'missing_required' => 'Faltan campos obligatorios para el proveedor.',
    ],

    'config' => [
        'host' => 'Host',
        'port' => 'Puerto',
        'username' => 'Usuario',
        'password' => 'Contraseña',
        'encryption' => 'Cifrado',
        'from_address' => 'Email remitente',
        'from_name' => 'Nombre remitente',
        'tenant_id' => 'Tenant ID',
        'client_id' => 'Client ID',
        'client_secret' => 'Client Secret',
        'redirect_uri' => 'Redirect URI',
        'root_folder' => 'Carpeta raíz',
        'account_id' => 'Account ID',
        'integration_key' => 'Integration Key',
        'user_id' => 'User ID',
        'private_key' => 'Private Key',
        'base_uri' => 'Base URI',
        'report_url' => 'URL del reporte',
    ],
];

