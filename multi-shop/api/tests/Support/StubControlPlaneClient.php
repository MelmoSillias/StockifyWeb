<?php

namespace App\Tests\Support;

use App\Onboarding\Application\Service\ControlPlaneGatewayInterface;

final class StubControlPlaneClient implements ControlPlaneGatewayInterface
{
    /** @var array<string, mixed>|null */
    public ?array $lastSignupPayload = null;

    /**
     * @return array<string, mixed>
     */
    public function fetchPublicPlans(string $applicationSlug = 'stockify'): array
    {
        return [
            'items' => [
                [
                    'id' => 'plan-starter',
                    'code' => 'starter',
                    'name' => 'Starter',
                    'priceCents' => 0,
                    'billingPeriod' => 'monthly',
                    'features' => [],
                    'quotas' => ['max_shops' => 1],
                ],
                [
                    'id' => 'plan-pro',
                    'code' => 'pro',
                    'name' => 'Pro',
                    'priceCents' => 9900,
                    'billingPeriod' => 'monthly',
                    'features' => [],
                    'quotas' => ['max_shops' => 5],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function signup(array $payload): array
    {
        $this->lastSignupPayload = $payload;

        return [
            'account' => [
                'id' => '00000000-0000-4000-8000-000000000001',
                'slug' => $payload['accountSlug'],
            ],
            'subscription' => ['planCode' => $payload['planCode']],
            'entitlement' => [
                'features' => ['stockify.multi_shop'],
                'quotas' => ['max_shops' => 1],
            ],
            'shopCredentials' => [
                'email' => $payload['adminEmail'],
                'temporaryPassword' => 'Temp123!',
            ],
            'stockifyLoginUrl' => 'http://localhost:5176/login',
        ];
    }

    public function completeSignup(array $payload): array
    {
        return [
            'tenantBinding' => [
                'accountId' => $payload['accountId'],
                'remoteTenantId' => $payload['remoteTenantId'],
                'remoteShopIds' => $payload['remoteShopIds'] ?? [],
            ],
        ];
    }
}
