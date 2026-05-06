<?php

return [
    'navigation_label' => 'Seguridad',
    'navigation_group' => 'Cuenta',
    'title' => 'Seguridad',
    'sections' => [
        'two_factor' => 'Autenticación de dos factores',
        'sessions' => 'Sesiones activas',
        'password' => 'Cambiar contraseña',
    ],
    'descriptions' => [
        'two_factor' => 'Use una app de autenticación (Google Authenticator, 1Password, Authy) para reforzar el acceso.',
        'sessions' => 'Lista de sesiones abiertas. Cierre una sesión para desconectarla de inmediato.',
        'password' => 'Su contraseña debe tener al menos 8 caracteres, con mayúsculas, minúsculas, números y símbolos.',
    ],
    'fields' => [
        'session_id' => 'Sesión',
        'user' => 'Usuario',
        'tenant' => 'Organización',
        'ip_address' => 'IP',
        'user_agent' => 'Navegador',
        'login_at' => 'Iniciada en',
        'last_activity_at' => 'Última actividad',
        'status' => 'Estado',
    ],
    'status' => [
        'active' => 'Activa',
        'closed' => 'Cerrada',
        'expired' => 'Expirada',
    ],
    'actions' => [
        'revoke' => 'Cerrar sesión',
        'revoke_confirm_heading' => '¿Cerrar esta sesión?',
        'revoke_confirm_description' => 'La sesión se desconectará de inmediato.',
        'update_password' => 'Actualizar contraseña',
    ],
    'sessions' => [
        'rate_limited' => 'Demasiados intentos de cierre de sesión. Inténtelo más tarde.',
        'unauthorized' => 'No puede cerrar esta sesión.',
        'not_found' => 'Sesión inválida.',
        'revoked' => 'Sesión cerrada.',
        'empty' => 'No hay sesiones activas registradas.',
        'empty_heading' => 'No hay sesiones activas',
        'empty_description' => 'Cuando haya sesiones activas, aparecerán aquí para que pueda cerrarlas rápidamente.',
    ],
    'password' => [
        'rate_limited' => 'Demasiados intentos de cambio de contraseña. Inténtelo más tarde.',
        'invalid_current' => 'La contraseña actual es incorrecta.',
        'updated' => 'Contraseña actualizada con éxito.',
        'attributes' => [
            'current_password' => 'contraseña actual',
            'password' => 'nueva contraseña',
            'password_confirmation' => 'confirmar nueva contraseña',
        ],
    ],
];
