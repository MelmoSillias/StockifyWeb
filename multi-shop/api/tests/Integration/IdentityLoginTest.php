<?php

namespace App\Tests\Integration;

use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use App\Tests\ApiTestCase;
use App\Tests\Support\StubControlPlaneClient;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class IdentityLoginTest extends ApiTestCase
{
    public function testLoginViaControlPlaneForLinkedIdentityUser(): void
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

        /** @var UserRepositoryInterface $users */
        $users = $container->get(UserRepositoryInterface::class);

        $user = new User('global-auth@test.local', 'global-auth', null, 'Global', 'Auth');
        $user->activate();
        $user->setIdentityId(Uuid::fromString((string) $signup['identityId']));
        $users->save($user);

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'global-auth@test.local',
            'password' => 'Password123!',
        ]));

        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($payload['access_token']);
        self::assertNotEmpty($payload['refresh_token']);
    }

    public function testLoginRejectsIdentityUserWithWrongPassword(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();
        $container = static::getContainer();

        /** @var StubControlPlaneClient $controlPlane */
        $controlPlane = $container->get(StubControlPlaneClient::class);
        $signup = $controlPlane->signup([
            'accountSlug' => 'auth-fail',
            'adminEmail' => 'fail-auth@test.local',
            'adminPassword' => 'Password123!',
            'planCode' => 'starter',
        ]);

        /** @var UserRepositoryInterface $users */
        $users = $container->get(UserRepositoryInterface::class);

        $user = new User('fail-auth@test.local', 'fail-auth', null, 'Fail', 'Auth');
        $user->activate();
        $user->setIdentityId(Uuid::fromString((string) $signup['identityId']));
        $users->save($user);

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'fail-auth@test.local',
            'password' => 'WrongPassword!',
        ]));

        self::assertResponseStatusCodeSame(401);
    }

    public function testLocalUserStillLogsInWithPasswordHash(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();
        $container = static::getContainer();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);
        /** @var UserRepositoryInterface $users */
        $users = $container->get(UserRepositoryInterface::class);

        $user = new User('local-auth@test.local', 'local-auth', null, 'Local', 'Auth');
        $user->setPasswordHash($hasher->hashPassword($user, 'Password123!'));
        $user->activate();
        $users->save($user);

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'local-auth@test.local',
            'password' => 'Password123!',
        ]));

        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($payload['access_token']);
    }
}
