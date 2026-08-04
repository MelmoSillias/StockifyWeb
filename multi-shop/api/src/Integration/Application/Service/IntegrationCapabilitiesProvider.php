<?php

namespace App\Integration\Application\Service;

final class IntegrationCapabilitiesProvider
{
    /** @return array<string, mixed> */
    public function getCapabilities(): array
    {
        return [
            'version' => '1.0',
            'api' => 'integration/v1',
            'operations' => [
                'health.check',
                'capabilities.list',
                'accounts.provision',
                'accounts.get',
                'accounts.update_entitlements',
                'accounts.suspend',
                'accounts.activate',
                'accounts.delete',
                'accounts.usage',
                'accounts.create_shop',
            ],
            'idempotency' => [
                'header' => 'Idempotency-Key',
                'supported_on' => ['POST /accounts'],
            ],
        ];
    }
}
