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

    /** @var array<string, string> email => password */
    private array $identityCredentials = [];

    /** @var array<string, string> email => identityId */
    private array $identityIds = [];

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
                'features' => ['stockify.multi_shop'],
                'quotas' => ['max_shops' => 1],
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
        );
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
            'features' => ['stockify.multi_shop'],
            'quotas' => ['max_shops' => 1],
            'updated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
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
