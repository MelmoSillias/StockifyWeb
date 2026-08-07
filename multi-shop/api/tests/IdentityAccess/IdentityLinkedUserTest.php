<?php

namespace App\Tests\IdentityAccess;

use App\IdentityAccess\Application\Command\ChangePassword\ChangePasswordCommand;
use App\IdentityAccess\Application\Command\ChangePassword\ChangePasswordHandler;
use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use App\Tests\ApiTestCase;
use App\Tests\Support\StubControlPlaneClient;
use Symfony\Component\Uid\Uuid;

final class IdentityLinkedUserTest extends ApiTestCase
{
    public function testIdentityLinkedUserHasNoLocalPasswordHash(): void
    {
        $this->initializeTestSchema();
        $container = static::getContainer();

        /** @var StubControlPlaneClient $controlPlane */
        $controlPlane = $container->get(StubControlPlaneClient::class);
        $signup = $controlPlane->signup([
            'accountSlug' => 'linked-user',
            'adminEmail' => 'linked-user@test.local',
            'adminPassword' => 'Password123!',
            'planCode' => 'starter',
        ]);

        /** @var UserRepositoryInterface $users */
        $users = $container->get(UserRepositoryInterface::class);

        $user = new User('linked-user@test.local', 'linked-user', null, 'Linked', 'User');
        $user->activate();
        $user->setIdentityId(Uuid::fromString((string) $signup['identityId']));
        $users->save($user);

        $stored = $users->findById($user->getId());
        self::assertNotNull($stored);
        self::assertNotNull($stored->getIdentityId());
        self::assertSame('', $stored->getPassword());
    }

    public function testChangePasswordIsRejectedForIdentityLinkedUser(): void
    {
        $this->initializeTestSchema();
        $container = static::getContainer();

        /** @var StubControlPlaneClient $controlPlane */
        $controlPlane = $container->get(StubControlPlaneClient::class);
        $signup = $controlPlane->signup([
            'accountSlug' => 'linked-pass',
            'adminEmail' => 'linked-pass@test.local',
            'adminPassword' => 'Password123!',
            'planCode' => 'starter',
        ]);

        /** @var UserRepositoryInterface $users */
        $users = $container->get(UserRepositoryInterface::class);

        $user = new User('linked-pass@test.local', 'linked-pass', null, 'Linked', 'Pass');
        $user->activate();
        $user->setIdentityId(Uuid::fromString((string) $signup['identityId']));
        $users->save($user);

        /** @var ChangePasswordHandler $handler */
        $handler = $container->get(ChangePasswordHandler::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Password is managed by your global identity account.');
        $handler->handle(new ChangePasswordCommand($user, 'Password123!', 'NewPassword456!'));
    }

    public function testChangePasswordEndpointRejectsIdentityLinkedUser(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();
        $container = static::getContainer();

        /** @var StubControlPlaneClient $controlPlane */
        $controlPlane = $container->get(StubControlPlaneClient::class);
        $signup = $controlPlane->signup([
            'accountSlug' => 'linked-api',
            'adminEmail' => 'linked-api@test.local',
            'adminPassword' => 'Password123!',
            'planCode' => 'starter',
        ]);

        /** @var UserRepositoryInterface $users */
        $users = $container->get(UserRepositoryInterface::class);

        $user = new User('linked-api@test.local', 'linked-api', null, 'Linked', 'Api');
        $user->activate();
        $user->setIdentityId(Uuid::fromString((string) $signup['identityId']));
        $users->save($user);

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'linked-api@test.local',
            'password' => 'Password123!',
        ]));
        self::assertResponseIsSuccessful();
        $auth = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/me/password', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.self::extractAccessToken($auth),
            'CONTENT_TYPE' => 'application/json',
        ], content: json_encode([
            'current_password' => 'Password123!',
            'new_password' => 'NewPassword456!',
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(403);
    }
}
