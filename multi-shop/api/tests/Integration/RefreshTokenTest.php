<?php

namespace App\Tests\Integration;

use App\IdentityAccess\Application\Service\RefreshTokenService;
use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use App\Tests\ApiTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RefreshTokenTest extends ApiTestCase
{
    public function testLoginRefreshRotatesToken(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();
        $container = static::getContainer();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);
        /** @var UserRepositoryInterface $users */
        $users = $container->get(UserRepositoryInterface::class);

        $user = new User('refresh-test@test.local', 'refresh-test', null, 'Refresh', 'Test');
        $user->setPasswordHash($hasher->hashPassword($user, 'Password123!'));
        $user->activate();
        $users->save($user);

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'refresh-test@test.local',
            'password' => 'Password123!',
        ]));
        self::assertResponseIsSuccessful();

        $loginPayload = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($loginPayload['access_token']);
        self::assertNotEmpty($loginPayload['refresh_token']);

        $plainRefresh = (string) $loginPayload['refresh_token'];

        $client->request('POST', '/api/token/refresh', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'refresh_token' => $plainRefresh,
        ], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();

        $refreshPayload = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($refreshPayload['access_token']);
        self::assertNotSame($plainRefresh, $refreshPayload['refresh_token']);

        $client->request('POST', '/api/token/refresh', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'refresh_token' => $plainRefresh,
        ], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(401);
    }

    public function testRefreshWorksWithHttpOnlyCookie(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();
        $container = static::getContainer();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);
        /** @var UserRepositoryInterface $users */
        $users = $container->get(UserRepositoryInterface::class);

        $user = new User('cookie-refresh@test.local', 'cookie-refresh', null, 'Cookie', 'Refresh');
        $user->setPasswordHash($hasher->hashPassword($user, 'Password123!'));
        $user->activate();
        $users->save($user);

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'cookie-refresh@test.local',
            'password' => 'Password123!',
        ]));
        self::assertResponseIsSuccessful();

        $refreshCookie = $client->getCookieJar()->get(RefreshTokenService::COOKIE_NAME, '/api');
        self::assertNotNull($refreshCookie);

        $client->request('POST', '/api/token/refresh');
        self::assertResponseIsSuccessful();
    }
}
