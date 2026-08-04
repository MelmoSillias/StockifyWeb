<?php

namespace App\Onboarding\Application\Service;

use Psr\Log\LoggerInterface;

final class PublicSignupService
{
    public function __construct(
        private readonly ControlPlaneGatewayInterface $controlPlaneClient,
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

        return $this->controlPlaneClient->signup([
            'accountName' => (string) ($data['accountName'] ?? ''),
            'accountSlug' => (string) ($data['accountSlug'] ?? ''),
            'billingEmail' => (string) ($data['billingEmail'] ?? $adminEmail),
            'adminEmail' => $adminEmail,
            'applicationSlug' => (string) ($data['applicationSlug'] ?? 'stockify'),
            'planCode' => $resolvedPlanCode,
        ]);
    }
}
