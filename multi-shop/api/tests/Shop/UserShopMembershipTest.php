<?php

namespace App\Tests\Shop;

use App\AccessAudit\Domain\Entity\Role;
use App\AccessAudit\Domain\Entity\UserRole;
use App\IdentityAccess\Domain\Entity\User;
use App\Integration\Domain\Entity\TenantAccount;
use App\Shop\Domain\Entity\Shop;
use App\Tests\ApiTestCase;
use App\Tests\Support\IntegrationJwtTestHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserShopMembershipTest extends ApiTestCase
{
    public function testTenantUserOnlyAccessesMemberShops(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();

        $fixture = $this->seedTenantWithTwoShopsAndMemberOfFirstOnly();
        $headers = $this->authenticateUser($client, $fixture['email'], 'Member123!');

        $headersA = $headers;
        $headersA['HTTP_X-Shop-Id'] = $fixture['shopAId'];
        $client->request('GET', '/api/products', [], [], $headersA);
        self::assertResponseIsSuccessful();

        $headersB = $headers;
        $headersB['HTTP_X-Shop-Id'] = $fixture['shopBId'];
        $client->request('GET', '/api/products', [], [], $headersB);
        self::assertResponseStatusCodeSame(403);

        $client->request('GET', '/api/me/shops', [], [], $headers);
        self::assertResponseIsSuccessful();
        $meShops = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $meShops['data']);
        self::assertSame($fixture['slugA'], $meShops['data'][0]['slug']);
    }

    public function testAddingMembershipGrantsSecondShopAccess(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();

        $fixture = $this->seedTenantWithTwoShopsAndMemberOfFirstOnly();
        $adminHeaders = $this->authenticateAdmin($client);

        $client->request(
            'POST',
            sprintf('/api/shops/%s/users/%s/membership', $fixture['shopBId'], $fixture['userId']),
            [],
            [],
            $adminHeaders,
        );
        self::assertResponseIsSuccessful();

        $headers = $this->authenticateUser($client, $fixture['email'], 'Member123!');

        $client->request('GET', '/api/me/shops', [], [], $headers);
        self::assertResponseIsSuccessful();
        $meShops = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(2, $meShops['data']);
        $slugs = array_column($meShops['data'], 'slug');
        sort($slugs);
        $expected = [$fixture['slugA'], $fixture['slugB']];
        sort($expected);
        self::assertSame($expected, $slugs);

        $headersB = $headers;
        $headersB['HTTP_X-Shop-Id'] = $fixture['shopBId'];
        $client->request('GET', '/api/products', [], [], $headersB);
        self::assertResponseIsSuccessful();
    }

    public function testPlatformOwnerStillRequiresShopHeader(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $shop = new Shop('Owner Shop', 'owner-shop');
        $em->persist($shop);

        $admin = $em->getRepository(User::class)->findOneBy(['email' => 'admin-test@stockify.local']);
        self::assertInstanceOf(User::class, $admin);
        if (!$admin->isPlatformOwner()) {
            $admin->promoteToPlatformOwner();
        }
        $em->flush();

        $headers = $this->authenticateAdmin($client);
        $client->request('GET', '/api/products', [], [], $headers);
        self::assertResponseStatusCodeSame(400);

        $headers['HTTP_X-Shop-Id'] = (string) $shop->getId();
        $client->request('GET', '/api/products', [], [], $headers);
        self::assertResponseIsSuccessful();
    }

    public function testCreateSecondShopAdminWithSameEmailAddsMembership(): void
    {
        IntegrationJwtTestHelper::ensureKeyPair();
        $this->initializeTestSchema();
        $client = static::createClient();
        $suffix = uniqid('', true);
        $externalAccountId = 'acme-membership-'.$suffix;
        $adminEmail = 'shared-admin-'.$suffix.'@acme.test';
        $headers = IntegrationJwtTestHelper::authHeaders('membership-idempotent');

        $client->request('POST', '/integration/v1/accounts', [], [], $headers, json_encode([
            'external_account_id' => $externalAccountId,
            'entitlements' => ['plan' => 'pro', 'max_shops' => 5],
        ]));
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/integration/v1/accounts/'.$externalAccountId.'/shops', [], [], $headers, json_encode([
            'name' => 'Acme One',
            'slug' => 'acme-one-'.substr(md5($suffix), 0, 8),
            'admin_email' => $adminEmail,
        ]));
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/integration/v1/accounts/'.$externalAccountId.'/shops', [], [], $headers, json_encode([
            'name' => 'Acme Two',
            'slug' => 'acme-two-'.substr(md5($suffix.'b'), 0, 8),
            'admin_email' => $adminEmail,
        ]));
        self::assertResponseStatusCodeSame(201);
        $secondShop = json_decode($client->getResponse()->getContent(), true);
        self::assertSame($adminEmail, $secondShop['data']['admin']['email']);
        self::assertArrayNotHasKey('temporary_password', $secondShop['data']['admin']);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => $adminEmail]);
        self::assertInstanceOf(User::class, $user);
        self::assertCount(2, $user->getShopIds());
        self::assertTrue($user->belongsToShop(\Symfony\Component\Uid\Uuid::fromString($secondShop['data']['id'])));
    }

    /**
     * @return array{shopAId: string, shopBId: string, userId: string, email: string, slugA: string, slugB: string}
     */
    private function seedTenantWithTwoShopsAndMemberOfFirstOnly(): array
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $suffix = uniqid('', true);
        $slugA = 'shop-a-'.substr(md5($suffix), 0, 8);
        $slugB = 'shop-b-'.substr(md5($suffix.'b'), 0, 8);
        $email = 'member-'.$suffix.'@acme.test';

        $tenant = new TenantAccount('tenant-membership-'.$suffix, ['plan' => 'pro', 'max_shops' => 5]);
        $tenant->markProvisioned();
        $em->persist($tenant);

        $shopA = new Shop('Shop A', $slugA);
        $shopA->setTenantAccountId($tenant->getId());
        $shopB = new Shop('Shop B', $slugB);
        $shopB->setTenantAccountId($tenant->getId());
        $em->persist($shopA);
        $em->persist($shopB);

        $admin = $em->getRepository(User::class)->findOneBy(['email' => 'admin-test@stockify.local']);
        self::assertInstanceOf(User::class, $admin);
        if (!$admin->isPlatformOwner()) {
            $admin->promoteToPlatformOwner();
        }

        $member = new User($email, 'member-'.substr(md5($suffix), 0, 8), 'placeholder', 'Member', 'Acme');
        $member->setPasswordHash($hasher->hashPassword($member, 'Member123!'));
        $member->activate();
        $member->assignToTenantAccount($tenant->getId());
        $member->assignToShop($shopA->getId());
        $member->syncSymfonyRoles(['gerant']);

        $gerant = $em->getRepository(Role::class)->findOneBy(['code' => 'gerant']);
        self::assertInstanceOf(Role::class, $gerant);
        $em->persist($member);
        $em->persist(new UserRole($member, $gerant));
        $em->flush();

        return [
            'shopAId' => (string) $shopA->getId(),
            'shopBId' => (string) $shopB->getId(),
            'userId' => (string) $member->getId(),
            'email' => $email,
            'slugA' => $slugA,
            'slugB' => $slugB,
        ];
    }

    /** @return array<string, string> */
    private function authenticateUser(KernelBrowser $client, string $email, string $password): array
    {
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => $password,
        ]));
        self::assertResponseIsSuccessful();
        $auth = json_decode($client->getResponse()->getContent(), true);

        return [
            'HTTP_AUTHORIZATION' => 'Bearer '.$auth['token'],
            'CONTENT_TYPE' => 'application/json',
        ];
    }
}
