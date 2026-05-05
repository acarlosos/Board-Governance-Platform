<?php

namespace App\Integrations;

use App\Enums\IntegrationProvider;
use App\Integrations\Drivers\DocuSignIntegrationDriver;
use App\Integrations\Drivers\IntegrationDriverInterface;
use App\Integrations\Drivers\LookerStudioIntegrationDriver;
use App\Integrations\Drivers\Microsoft365IntegrationDriver;
use App\Integrations\Drivers\OneDriveIntegrationDriver;
use App\Integrations\Drivers\SmtpIntegrationDriver;
use App\Integrations\Drivers\TeamsIntegrationDriver;
use App\Integrations\Drivers\ZoomIntegrationDriver;

final class IntegrationDriverFactory
{
    public function resolve(IntegrationProvider $provider): IntegrationDriverInterface
    {
        return match ($provider) {
            IntegrationProvider::Smtp => app(SmtpIntegrationDriver::class),
            IntegrationProvider::Microsoft365 => app(Microsoft365IntegrationDriver::class),
            IntegrationProvider::OneDrive => app(OneDriveIntegrationDriver::class),
            IntegrationProvider::DocuSign => app(DocuSignIntegrationDriver::class),
            IntegrationProvider::Teams => app(TeamsIntegrationDriver::class),
            IntegrationProvider::Zoom => app(ZoomIntegrationDriver::class),
            IntegrationProvider::LookerStudio => app(LookerStudioIntegrationDriver::class),
        };
    }
}

