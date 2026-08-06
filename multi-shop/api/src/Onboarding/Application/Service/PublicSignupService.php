<?php

namespace App\Onboarding\Application\Service;

use Psr\Log\LoggerInterface;

final class PublicSignupService
{
    private const COMPLETE_SIGNUP_ATTEMPTS = 3;
    private const COMPLETE_SIGNUP_BACKOFF_MS = [100, 250, 500];

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
        $identityId = isset($controlPlaneResult['identityId']) ? (string) $controlPlaneResult['identityId'] : null;
        $emailVerifiedAt = isset($controlPlaneResult['emailVerifiedAt']) && is_string($controlPlaneResult['emailVerifiedAt'])
            ? $controlPlaneResult['emailVerifiedAt']
            : null;

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
            identityId: $identityId,
            adminFirstName: self::optionalString($data['firstName'] ?? null),
            adminLastName: self::optionalString($data['lastName'] ?? null),
            adminPhone: self::optionalString($data['phone'] ?? null),
            shopPhone: self::optionalString($data['shopPhone'] ?? null),
            shopAddress: self::optionalString($data['shopAddress'] ?? null),
            emailVerifiedAt: $emailVerifiedAt,
        );

        $completionPayload = [
            'accountId' => $accountId,
            'applicationSlug' => $applicationSlug,
            'remoteTenantId' => $accountId,
            'remoteShopIds' => [(string) $localResult->shop->getId()],
        ];

        $completion = $this->completeSignupWithRetry($completionPayload);

        return [
            'account' => $controlPlaneResult['account'],
            'subscription' => $controlPlaneResult['subscription'],
            'tenantBinding' => $completion['tenantBinding'] ?? null,
            'bindingPending' => (bool) ($completion['bindingPending'] ?? false),
            'shopCredentials' => $this->buildShopCredentials($localResult, $adminEmail),
            'stockifyLoginUrl' => $controlPlaneResult['stockifyLoginUrl'] ?? null,
        ];
    }

    /**
     * Best-effort rebind for ops reconcile (idempotent on Control Plane).
     *
     * @param list<string> $remoteShopIds
     *
     * @return array<string, mixed>
     */
    public function reconcileBinding(
        string $accountId,
        array $remoteShopIds,
        string $applicationSlug = 'stockify',
    ): array {
        return $this->completeSignupWithRetry([
            'accountId' => $accountId,
            'applicationSlug' => $applicationSlug,
            'remoteTenantId' => $accountId,
            'remoteShopIds' => $remoteShopIds,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function completeSignupWithRetry(array $payload): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::COMPLETE_SIGNUP_ATTEMPTS; ++$attempt) {
            try {
                $completion = $this->controlPlaneClient->completeSignup($payload);

                return [
                    'tenantBinding' => $completion['tenantBinding'] ?? $completion,
                    'bindingPending' => false,
                ];
            } catch (\Throwable $e) {
                $lastException = $e;
                $this->logger->warning('Signup completion attempt failed.', [
                    'accountId' => $payload['accountId'] ?? null,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < self::COMPLETE_SIGNUP_ATTEMPTS) {
                    $delayMs = self::COMPLETE_SIGNUP_BACKOFF_MS[$attempt - 1] ?? 500;
                    usleep($delayMs * 1000);
                }
            }
        }

        $this->logger->critical('Signup completion failed after local provision; binding left pending.', [
            'accountId' => $payload['accountId'] ?? null,
            'remoteShopIds' => $payload['remoteShopIds'] ?? [],
            'error' => $lastException?->getMessage(),
        ]);

        return [
            'tenantBinding' => null,
            'bindingPending' => true,
        ];
    }

    private static function optionalString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return '' === $trimmed ? null : $trimmed;
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
