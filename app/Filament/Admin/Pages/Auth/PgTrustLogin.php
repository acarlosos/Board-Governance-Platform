<?php

namespace App\Filament\Admin\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

final class PgTrustLogin extends BaseLogin
{
    protected static string $layout = 'filament.admin.auth.pgtrust-layout';

    protected string $view = 'filament.admin.auth.pgtrust-login';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            Flex::make([
                $this->getRememberFormComponent(),
                Html::make(fn (): HtmlString => new HtmlString(
                    filament()->hasPasswordReset()
                        ? Blade::render(
                            '<a class="bgp-login__forgot" href="{{ filament()->getRequestPasswordResetUrl() }}">{{ __(\'filament-panels::auth/pages/login.actions.request_password_reset.label\') }}</a>'
                        )
                        : ''
                )),
            ])->alignBetween()->verticallyAlignCenter(),
        ]);
    }
}

