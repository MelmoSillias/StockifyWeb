<?php

namespace App\Tests\Support;

use App\Onboarding\Application\Service\ControlPlaneException;
use App\Onboarding\Application\Service\ControlPlaneGatewayInterface;

final class StubControlPlaneClient implements ControlPlaneGatewayInterface
{
    /** @var array<string, mixed>|null */
    public ?array $lastSignupPayload = null;

    /** @var array<string, mixed>|null */
    public ?array $lastCompleteSignupPayload = null;

    public int $completeSignupCalls = 0;

    public int $failCompleteSignupTimes = 0;

    public int $pullEntitlementsCalls = 0;

    public bool $failPullEntitlements = false;

    /** @var array{features: list<string>, quotas: array<string, int|float>, updated_at: ?string}|null */
    public ?array $pullEntitlementsResponse = null;

    /** @var array{identityId: string, emailVerified: bool, emailVerifiedAt: ?string}|null */
    public ?array $pullIdentityVerificationResponse = null;

    /** @var array<string, string> email => password */
    private array $identityCredentials = [];

    /** @var array<string, string> email => identityId */
    private array $identityIds = [];

    /** @var array<string, bool> email => verified */
    private array $identityEmailVerified = [];

    /**
     * @return array<string, mixed>
     */
    /** @var array<string, mixed>|null */
    public ?array $lastQuoteRequestPayload = null;

    public function fetchPublicPlans(string $applicationSlug = 'stockify'): array
    {
        return [
            'items' => [
                [
                    'id' => 'plan-starter',
                    'code' => 'starter',
                    'name' => 'Starter',
                    'priceFcfa' => 4500,
                    'priceCents' => 4500,
                    'billingPeriod' => 'monthly',
                    'features' => [],
                    'quotas' => ['max_shops' => 1, 'max_users' => 3],
                ],
                [
                    'id' => 'plan-essentiels',
                    'code' => 'essentiels',
                    'name' => 'Essentiels',
                    'priceFcfa' => 8000,
                    'priceCents' => 8000,
                    'billingPeriod' => 'monthly',
                    'features' => [
                        ['code' => 'stockify.multi_shop'],
                        ['code' => 'stockify.orders'],
                        ['code' => 'stockify.quotes'],
                    ],
                    'quotas' => ['max_shops' => 3, 'max_users' => 12],
                ],
                [
                    'id' => 'plan-pro',
                    'code' => 'pro',
                    'name' => 'Pro',
                    'priceFcfa' => 20000,
                    'priceCents' => 20000,
                    'billingPeriod' => 'monthly',
                    'features' => [
                        ['code' => 'stockify.multi_shop'],
                        ['code' => 'stockify.orders'],
                        ['code' => 'stockify.quotes'],
                        ['code' => 'stockify.analytics'],
                        ['code' => 'stockify.suppliers'],
                    ],
                    'quotas' => ['max_shops' => 7, 'max_users' => 50],
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
        $slug = (string) ($payload['accountSlug'] ?? 'account');
        $accountId = $this->deterministicAccountId($slug);
        $adminEmail = strtolower(trim((string) ($payload['adminEmail'] ?? '')));
        $identityId = null;

        if ('' !== $adminEmail && !empty($payload['adminPassword'])) {
            $identityId = $this->deterministicIdentityId($adminEmail);
            $this->identityIds[$adminEmail] = $identityId;
            $this->identityCredentials[$adminEmail] = (string) $payload['adminPassword'];
        }

        return [
            'account' => [
                'id' => $accountId,
                'slug' => $slug,
            ],
            'subscription' => ['planCode' => $payload['planCode']],
            'entitlement' => [
                'features' => [],
                'quotas' => ['max_shops' => 1, 'max_users' => 3],
            ],
            'identityId' => $identityId,
            'shopCredentials' => [
                'email' => $payload['adminEmail'],
                'temporaryPassword' => 'Temp123!',
            ],
            'stockifyLoginUrl' => 'http://localhost:5176/login',
        ];
    }

    public function completeSignup(array $payload): array
    {
        ++$this->completeSignupCalls;
        $this->lastCompleteSignupPayload = $payload;

        if ($this->failCompleteSignupTimes > 0) {
            --$this->failCompleteSignupTimes;
            throw new ControlPlaneException('Simulated completeSignup failure.', 503);
        }

        return [
            'tenantBinding' => [
                'accountId' => $payload['accountId'],
                'remoteTenantId' => $payload['remoteTenantId'],
                'remoteShopIds' => $payload['remoteShopIds'] ?? [],
            ],
        ];
    }

    public function exchangeIdentityToken(string $email, string $password, string $applicationSlug = 'stockify'): string
    {
        $normalizedEmail = strtolower(trim($email));
        $storedPassword = $this->identityCredentials[$normalizedEmail] ?? null;
        if (null === $storedPassword || !hash_equals($storedPassword, $password)) {
            throw new ControlPlaneException('Invalid credentials.', 401);
        }

        $identityId = $this->identityIds[$normalizedEmail] ?? $this->deterministicIdentityId($normalizedEmail);

        return IdentityAssertionTestHelper::createAssertion(
            $identityId,
            $normalizedEmail,
            [],
            $applicationSlug,
            'sim-saas-admin',
            $this->identityEmailVerified[$normalizedEmail] ?? false,
        );
    }

    public function resendVerificationEmail(string $email): void
    {
    }

    public function registerIdentityCredentials(
        string $email,
        string $password,
        string $identityId,
        bool $emailVerified = false,
    ): void {
        $normalizedEmail = strtolower(trim($email));
        $this->identityCredentials[$normalizedEmail] = $password;
        $this->identityIds[$normalizedEmail] = $identityId;
        $this->identityEmailVerified[$normalizedEmail] = $emailVerified;
    }

    /**
     * @return array{identityId: string, emailVerified: bool, emailVerifiedAt: ?string}
     */
    public function pullIdentityVerification(string $identityId): array
    {
        if (null !== $this->pullIdentityVerificationResponse) {
            return $this->pullIdentityVerificationResponse;
        }

        return [
            'identityId' => $identityId,
            'emailVerified' => false,
            'emailVerifiedAt' => null,
        ];
    }

    /**
     * @return array{features: list<string>, quotas: array<string, int|float>, updated_at: ?string}
     */
    public function pullEntitlements(string $externalAccountId, string $applicationSlug = 'stockify'): array
    {
        ++$this->pullEntitlementsCalls;

        if ($this->failPullEntitlements) {
            throw new ControlPlaneException('Simulated entitlement pull failure.', 503);
        }

        if (null !== $this->pullEntitlementsResponse) {
            return $this->pullEntitlementsResponse;
        }

        return [
            'features' => [],
            'quotas' => ['max_shops' => 10, 'max_users' => 50],
            'updated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function submitQuoteRequest(array $payload): array
    {
        $this->lastQuoteRequestPayload = $payload;

        return [
            'id' => 'quote-request-stub',
            'applicationSlug' => $payload['applicationSlug'] ?? 'stockify',
            'contactName' => $payload['contactName'] ?? '',
            'email' => $payload['email'] ?? '',
            'status' => 'new',
        ];
    }

    private function deterministicAccountId(string $slug): string
    {
        $hex = substr(hash('sha256', 'stub-account:'.$slug), 0, 32);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            '4'.substr($hex, 13, 3),
            '8'.substr($hex, 17, 3),
            substr($hex, 20, 12),
        );
    }

    private function deterministicIdentityId(string $email): string
    {
        $hex = substr(hash('sha256', 'stub-identity:'.$email), 0, 32);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            '4'.substr($hex, 13, 3),
            '8'.substr($hex, 17, 3),
            substr($hex, 20, 12),
        );
    }
}
