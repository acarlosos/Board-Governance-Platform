<?php

namespace App\Integrations;

use App\Enums\IntegrationProvider;

final class IntegrationConfigSchemaRegistry
{
    /**
     * @return array<string, array{required: bool, secret: bool}>
     */
    public static function schemaFor(IntegrationProvider $provider): array
    {
        return match ($provider) {
            IntegrationProvider::Smtp => [
                'host' => ['required' => true, 'secret' => false],
                'port' => ['required' => true, 'secret' => false],
                'username' => ['required' => true, 'secret' => false],
                'password' => ['required' => true, 'secret' => true],
                'encryption' => ['required' => false, 'secret' => false],
                'from_address' => ['required' => true, 'secret' => false],
                'from_name' => ['required' => false, 'secret' => false],
            ],
            IntegrationProvider::Microsoft365 => [
                'tenant_id' => ['required' => true, 'secret' => false],
                'client_id' => ['required' => true, 'secret' => false],
                'client_secret' => ['required' => true, 'secret' => true],
                'redirect_uri' => ['required' => true, 'secret' => false],
            ],
            IntegrationProvider::OneDrive => [
                'tenant_id' => ['required' => true, 'secret' => false],
                'client_id' => ['required' => true, 'secret' => false],
                'client_secret' => ['required' => true, 'secret' => true],
                'root_folder' => ['required' => false, 'secret' => false],
            ],
            IntegrationProvider::DocuSign => [
                'account_id' => ['required' => true, 'secret' => false],
                'integration_key' => ['required' => true, 'secret' => false],
                'user_id' => ['required' => true, 'secret' => false],
                'private_key' => ['required' => true, 'secret' => true],
                'base_uri' => ['required' => true, 'secret' => false],
            ],
            IntegrationProvider::Teams => [
                'tenant_id' => ['required' => true, 'secret' => false],
                'client_id' => ['required' => true, 'secret' => false],
                'client_secret' => ['required' => true, 'secret' => true],
            ],
            IntegrationProvider::Zoom => [
                'account_id' => ['required' => true, 'secret' => false],
                'client_id' => ['required' => true, 'secret' => false],
                'client_secret' => ['required' => true, 'secret' => true],
            ],
            IntegrationProvider::LookerStudio => [
                'report_url' => ['required' => true, 'secret' => false],
            ],
        };
    }

    /**
     * @return list<string>
     */
    public static function requiredKeys(IntegrationProvider $provider): array
    {
        $required = [];
        foreach (self::schemaFor($provider) as $key => $meta) {
            if ($meta['required']) {
                $required[] = $key;
            }
        }
        return $required;
    }

    /**
     * @return list<string>
     */
    public static function secretKeys(IntegrationProvider $provider): array
    {
        $secrets = [];
        foreach (self::schemaFor($provider) as $key => $meta) {
            if ($meta['secret']) {
                $secrets[] = $key;
            }
        }
        return $secrets;
    }
}

