<?php

return [
    'navigation_label' => 'Security',
    'navigation_group' => 'Account',
    'title' => 'Security',
    'sections' => [
        'two_factor' => 'Two-factor authentication',
        'sessions' => 'Active sessions',
        'password' => 'Change password',
    ],
    'descriptions' => [
        'two_factor' => 'Use an authenticator app (Google Authenticator, 1Password, Authy) to strengthen sign-in.',
        'sessions' => 'List of open sessions. Revoke a session to sign it out immediately.',
        'password' => 'Your password must be at least 8 characters and include uppercase, lowercase, numbers and symbols.',
    ],
    'fields' => [
        'session_id' => 'Session',
        'user' => 'User',
        'tenant' => 'Organisation',
        'ip_address' => 'IP',
        'user_agent' => 'User agent',
        'login_at' => 'Started at',
        'last_activity_at' => 'Last activity',
        'status' => 'Status',
    ],
    'status' => [
        'active' => 'Active',
        'closed' => 'Closed',
        'expired' => 'Expired',
    ],
    'actions' => [
        'revoke' => 'Revoke session',
        'revoke_confirm_heading' => 'Revoke this session?',
        'revoke_confirm_description' => 'The session will be signed out immediately.',
        'update_password' => 'Update password',
    ],
    'sessions' => [
        'rate_limited' => 'Too many session revocation attempts. Try again shortly.',
        'unauthorized' => 'You cannot revoke this session.',
        'not_found' => 'Invalid session.',
        'revoked' => 'Session revoked.',
        'empty' => 'No active sessions on record.',
    ],
    'password' => [
        'rate_limited' => 'Too many password change attempts. Try again shortly.',
        'invalid_current' => 'Current password is incorrect.',
        'updated' => 'Password updated successfully.',
        'attributes' => [
            'current_password' => 'current password',
            'password' => 'new password',
        ],
    ],
];
