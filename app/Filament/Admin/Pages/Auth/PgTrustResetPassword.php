<?php

namespace App\Filament\Admin\Pages\Auth;

use Filament\Auth\Pages\PasswordReset\ResetPassword as BaseResetPassword;

final class PgTrustResetPassword extends BaseResetPassword
{
    protected static string $layout = 'filament.admin.auth.pgtrust-layout';

    protected string $view = 'filament.admin.auth.pgtrust-password-reset';
}

