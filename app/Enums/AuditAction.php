<?php

namespace App\Enums;

enum AuditAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case StatusChanged = 'status_changed';
    case Login = 'login';
    case Logout = 'logout';
    case FailedLogin = 'failed_login';
    case TwoFactorEnabled = 'two_factor_enabled';
    case TwoFactorDisabled = 'two_factor_disabled';
    case SessionRevoked = 'session_revoked';
    case SessionExpired = 'session_expired';
    case PasswordChanged = 'password_changed';
}

