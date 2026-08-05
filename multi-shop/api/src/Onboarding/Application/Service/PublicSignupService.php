<?php

namespace App\Onboarding\Application\Service;

use Psr\Log\LoggerInterface;

final class PublicSignupService
{
    public function __construct(
        private readonly ControlPlaneGatewayInterface $controlPlaneClient,
        private readonly LocalSignupProvisioner $localSignupProvisioner,
        private readonly SignupPlanPolicy $signupPlanPolicy,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function signup(array $data): array
    {
        $requestedPlanCode = isset($data['planCode']) ? (string) $data['planCode'] : null;
        $resolvedPlanCode = $this->signupPlanPolicy->resolvePlanCode($requestedPlanCode);

        if (null !== $requestedPlanCode && $requestedPlanCode !== $resolvedPlanCode) {
            $this->logger->info('Signup plan overridden until payment is available.', [
                'requestedPlanCode' => $requestedPlanCode,
                'resolvedPlanCode' => $resolvedPlanCode,
            ]);
        }

        $adminEmail = (string) ($data['adminEmail'] ?? $data['billingEmail'] ?? '');
        $applicationSlug = (string) ($data['applicationSlug'] ?? 'stockify');

        $controlPlaneResult = $this->controlPlaneClient->signup([
            'accountName' => (string) ($data['accountName'] ?? ''),
            'accountSlug' => (string) ($data['accountSlug'] ?? ''),
            'billingEmail' => (string) ($data['billingEmail'] ?? $adminEmail),
            'adminEmail' => $adminEmail,
            'adminPassword' => isset($data['adminPassword']) ? (string) $data['adminPassword'] : null,
            'applicationSlug' => $applicationSlug,
            'planCode' => $resolvedPlanCode,
            'skipRemoteIntegration' => true,
        ]);

        $accountId = (string) ($controlPlaneResult['account']['id'] ?? '');
        if ('' === $accountId) {
            throw new ControlPlaneException('Control plane signup did not return an account id.', 502);
        }

        $entitlement = is_array($controlPlaneResult['entitlement'] ?? null) ? $controlPlaneResult['entitlement'] : [];

        $localResult = $this->localSignupProvisioner->provision(
            externalAccountId: $accountId,
            accountName: (string) ($data['accountName'] ?? ''),
            accountSlug: (string) ($data['accountSlug'] ?? ''),
            adminEmail: $adminEmail,
            adminPassword: isset($data['adminPassword']) ? (string) $data['adminPassword'] : null,
            entitlements: [
                'features' => is_array($entitlement['features'] ?? null) ? $entitlement['features'] : [],
                'quotas' => is_array($entitlement['quotas'] ?? null) ? $entitlement['quotas'] : [],
            ],
        );

        $completion = $this->controlPlaneClient->completeSignup([
            'accountId' => $accountId,
            'applicationSlug' => $applicationSlug,
            'remoteTenantId' => $accountId,
            'remoteShopIds' => [(string) $localResult->shop->getId()],
        ]);

        return [
            'account' => $controlPlaneResult['account'],
            'subscription' => $controlPlaneResult['subscription'],
            'tenantBinding' => $completion['tenantBinding'] ?? null,
            'shopCredentials' => $this->buildShopCredentials($localResult, $adminEmail),
            'stockifyLoginUrl' => $controlPlaneResult['stockifyLoginUrl'] ?? null,
        ];
    }

    private function buildShopCredentials(
        \App\Integration\Application\Command\CreateTenantShop\CreateTenantShopResult $result,
        string $adminEmail,
    ): array {
        $credentials = [
            'email' => $result->adminEmail ?? $adminEmail,
        ];

        if (null !== $result->temporaryPassword && '' !== $result->temporaryPassword) {
            $credentials['temporaryPassword'] = $result->temporaryPassword;
        } else {
            $credentials['passwordProvided'] = true;
        }

        return $credentials;
    }
}
