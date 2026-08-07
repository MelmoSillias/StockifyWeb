<?php

namespace App\Tests\IdentityAccess;

use App\IdentityAccess\Application\Service\EmailVerificationSyncService;
use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Infrastructure\Persistence\Doctrine\DoctrineUserRepository;
use App\Tests\ApiTestCase;
use App\Tests\Support\StubControlPlaneClient;
use Symfony\Component\Uid\Uuid;

final class EmailVerificationSyncServiceTest extends ApiTestCase
{
    public function testPullSyncMirrorsVerifiedStateFromControlPlane(): void
    {
        $this->initializeTestSchema();
        self::bootKernel(['environment' => 'test']);
        $container = static::getContainer();

        $identityId = Uuid::v7();
        $user = new User('owner@sync.test', 'owner', 'hash', 'Owner', 'Sync');
        $user->setIdentityId($identityId);
        $user->activate();

        /** @var DoctrineUserRepository $users */
        $users = $container->get(DoctrineUserRepository::class);
        $users->save($user);

        /** @var StubControlPlaneClient $controlPlane */
        $controlPlane = $container->get(StubControlPlaneClient::class);
        $controlPlane->pullIdentityVerificationResponse = [
            'identityId' => (string) $identityId,
            'emailVerified' => true,
            'emailVerifiedAt' => '2026-08-07T01:00:00+00:00',
        ];

        /** @var EmailVerificationSyncService $service */
        $service = $container->get(EmailVerificationSyncService::class);
        self::assertTrue($service->syncFromControlPlane($user));

        $reloaded = $users->findById($user->getId());
        self::assertNotNull($reloaded);
        self::assertTrue($reloaded->isEmailVerified());
    }

    public function testLoginAssertionSyncMirrorsVerifiedStateFromControlPlane(): void
    {
        $this->initializeTestSchema();
        self::bootKernel(['environment' => 'test']);
        $container = static::getContainer();

        $identityId = Uuid::v7();
        $user = new User('owner@assertion.test', 'owner', 'hash', 'Owner', 'Assertion');
        $user->setIdentityId($identityId);
        $user->activate();

        /** @var DoctrineUserRepository $users */
        $users = $container->get(DoctrineUserRepository::class);
        $users->save($user);

        /** @var StubControlPlaneClient $controlPlane */
        $controlPlane = $container->get(StubControlPlaneClient::class);
        $controlPlane->registerIdentityCredentials(
            'owner@assertion.test',
            'Password123!',
            (string) $identityId,
            true,
        );

        /** @var EmailVerificationSyncService $service */
        $service = $container->get(EmailVerificationSyncService::class);
        self::assertTrue($service->syncFromControlPlane($user, 'Password123!'));

        $reloaded = $users->findById($user->getId());
        self::assertNotNull($reloaded);
        self::assertTrue($reloaded->isEmailVerified());
    }
}
