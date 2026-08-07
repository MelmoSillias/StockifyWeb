<?php

namespace App\Onboarding\Application\Service;

interface ControlPlaneGatewayInterface
{
    /**
     * @return array<string, mixed>
     */
    public function fetchPublicPlans(string $applicationSlug = 'stockify'): array;

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function signup(array $payload): array;

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function completeSignup(array $payload): array;

    public function exchangeIdentityToken(string $email, string $password, string $applicationSlug = 'stockify'): string;

    public function resendVerificationEmail(string $email): void;

    /**
     * @return array{features: list<string>, quotas: array<string, int|float>, updated_at: ?string}
     */
    public function pullEntitlements(string $externalAccountId, string $applicationSlug = 'stockify'): array;

    /**
     * @return array{identityId: string, emailVerified: bool, emailVerifiedAt: ?string}
     */
    public function pullIdentityVerification(string $identityId): array;

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function submitQuoteRequest(array $payload): array;
}
