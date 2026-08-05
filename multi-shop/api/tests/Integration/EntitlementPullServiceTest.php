<?php

namespace App\Tests\Integration;

use App\Integration\Application\Service\EntitlementPullService;
use App\Integration\Application\Service\TenantEntitlementResolver;
use App\Integration\Application\Service\TenantFeatureGuard;
use App\Integration\Domain\Entity\TenantAccount;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\Tests\ApiTestCase;
use App\Tests\Support\StubControlPlaneClient;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class EntitlementPullServiceTest extends ApiTestCase
{
    public function testStaleSnapshotIsPulledAndUpdated(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();
        $container = static::getContainer();

        /** @var StubControlPlaneClient $controlPlane */
        $controlPlane = $container->get(StubControlPlaneClient::class);
        $controlPlane->pullEntitlementsResponse = [
            'features' => ['stockify.multi_shop', 'stockify.analytics'],
            'quotas' => ['max_shops' => 5],
            'updated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        /** @var TenantAccountRepositoryInterface $tenants */
        $tenants = $container->get(TenantAccountRepositoryInterface::class);
        $account = new TenantAccount('ext-stale-'.uniqid('', true), [
            'features' => ['stockify.multi_shop'],
            'quotas' => ['max_shops' => 1],
        ]);
        $account->markProvisioned();
        $tenants->save($account);

        self::assertNull($account->getLastSyncedAt());

        /** @var EntitlementPullService $pullService */
        $pullService = $container->get(EntitlementPullService::class);
        $pullService->ensureFresh($account);

        self::assertSame(1, $controlPlane->pullEntitlementsCalls);
        self::assertNotNull($account->getLastSyncedAt());
        self::assertContains('stockify.analytics', $account->getEntitlementsSnapshot()['features']);
        self::assertSame(5, $account->getEntitlementsSnapshot()['quotas']['max_shops']);

        // Fresh snapshot: no second pull.
        $pullService->ensureFresh($account);
        self::assertSame(1, $controlPlane->pullEntitlementsCalls);

        unset($client);
    }

    public function testPullFailureKeepsLastSnapshotFailOpenKnownFailClosedUnknown(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();
        $container = static::getContainer();

        /** @var StubControlPlaneClient $controlPlane */
        $controlPlane = $container->get(StubControlPlaneClient::class);
        $controlPlane->failPullEntitlements = true;

        /** @var TenantAccountRepositoryInterface $tenants */
        $tenants = $container->get(TenantAccountRepositoryInterface::class);
        $account = new TenantAccount('ext-fail-'.uniqid('', true), [
            'features' => ['stockify.multi_shop'],
            'quotas' => ['max_shops' => 2],
        ]);
        $account->markProvisioned();
        // Force staleness without a prior successful sync.
        $tenants->save($account);

        /** @var EntitlementPullService $pullService */
        $pullService = $container->get(EntitlementPullService::class);
        $pullService->ensureFresh($account);

        self::assertSame(1, $controlPlane->pullEntitlementsCalls);
        self::assertNull($account->getLastSyncedAt());
        self::assertSame(['stockify.multi_shop'], $account->getEntitlementsSnapshot()['features']);

        /** @var TenantEntitlementResolver $resolver */
        $resolver = $container->get(TenantEntitlementResolver::class);
        self::assertTrue($resolver->hasFeature($account, 'stockify.multi_shop'));
        self::assertFalse($resolver->hasFeature($account, 'stockify.analytics'));

        /** @var TenantFeatureGuard $guard */
        $guard = $container->get(TenantFeatureGuard::class);
        $guard->assertFeatureForTenantAccountId($account->getId(), 'stockify.multi_shop');

        $this->expectException(AccessDeniedHttpException::class);
        $guard->assertFeatureForTenantAccountId($account->getId(), 'stockify.analytics');

        unset($client);
    }

    public function testFreshSnapshotDoesNotCallControlPlane(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();
        $container = static::getContainer();

        /** @var StubControlPlaneClient $controlPlane */
        $controlPlane = $container->get(StubControlPlaneClient::class);

        /** @var TenantAccountRepositoryInterface $tenants */
        $tenants = $container->get(TenantAccountRepositoryInterface::class);
        $account = new TenantAccount('ext-fresh-'.uniqid('', true), [
            'features' => ['stockify.multi_shop'],
            'quotas' => ['max_shops' => 1],
        ]);
        $account->markProvisioned();
        $account->updateEntitlements([
            'features' => ['stockify.multi_shop'],
            'quotas' => ['max_shops' => 1],
        ]);
        $tenants->save($account);

        /** @var EntitlementPullService $pullService */
        $pullService = $container->get(EntitlementPullService::class);
        $pullService->ensureFresh($account);

        self::assertSame(0, $controlPlane->pullEntitlementsCalls);

        unset($client);
    }
}
