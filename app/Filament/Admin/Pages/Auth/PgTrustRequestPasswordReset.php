<?php

namespace App\Filament\Admin\Pages\Auth;

use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;

final class PgTrustRequestPasswordReset extends BaseRequestPasswordReset
{
    protected static string $layout = 'filament.admin.auth.pgtrust-layout';

    protected string $view = 'filament.admin.auth.pgtrust-password-reset-request';
}

