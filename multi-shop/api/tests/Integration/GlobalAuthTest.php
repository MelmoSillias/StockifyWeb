<?php

namespace App\Tests\Integration;

use App\IdentityAccess\Application\Service\GlobalAuthDisabledException;
use App\IdentityAccess\Application\Service\GlobalAuthService;
use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use App\IdentityAccess\Security\IdentityAssertionValidator;
use App\Onboarding\Application\Service\ControlPlaneGatewayInterface;
use App\Tests\ApiTestCase;
use App\Tests\Support\StubControlPlaneClient;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class GlobalAuthTest extends ApiTestCase
{
    public function testGlobalAuthDisabledWhenFeatureFlagOff(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $service = new GlobalAuthService(
            false,
            $container->get(ControlPlaneGatewayInterface::class),
            $container->get(IdentityAssertionValidator::class),
            $container->get(UserRepositoryInterface::class),
            $container->get(JWTTokenManagerInterface::class),
        );

        $this->expectException(GlobalAuthDisabledException::class);
        $service->authenticate('disabled@test.local', 'Password123!');
    }

    public function testGlobalAuthReturnsJwtForLinkedUser(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();
        $container = static::getContainer();

        /** @var StubControlPlaneClient $controlPlane */
        $controlPlane = $container->get(StubControlPlaneClient::class);
        $signup = $controlPlane->signup([
            'accountSlug' => 'auth-test',
            'adminEmail' => 'global-auth@test.local',
            'adminPassword' => 'Password123!',
            'planCode' => 'starter',
        ]);

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);
        /** @var UserRepositoryInterface $users */
        $users = $container->get(UserRepositoryInterface::class);

        $user = new User('global-auth@test.local', 'global-auth', 'placeholder', 'Global', 'Auth');
        $user->setPasswordHash($hasher->hashPassword($user, 'Password123!'));
        $user->activate();
        $user->setIdentityId(Uuid::fromString((string) $signup['identityId']));
        $users->save($user);

        $client->request('POST', '/api/auth/global', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'global-auth@test.local',
            'password' => 'Password123!',
        ]));

        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($payload['token']);
    }

    public function testGlobalAuthReturns403WhenNoLocalProfile(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();
        $container = static::getContainer();

        /** @var StubControlPlaneClient $controlPlane */
        $controlPlane = $container->get(StubControlPlaneClient::class);
        $controlPlane->signup([
            'accountSlug' => 'orphan-global',
            'adminEmail' => 'orphan-global@test.local',
            'adminPassword' => 'Password123!',
            'planCode' => 'starter',
        ]);

        $client->request('POST', '/api/auth/global', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'orphan-global@test.local',
            'password' => 'Password123!',
        ]));

        self::assertResponseStatusCodeSame(403);
    }
}
