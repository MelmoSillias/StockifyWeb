<?php

namespace App\Tests\Shop;

use App\AccessAudit\Domain\Entity\Role;
use App\AccessAudit\Domain\Entity\UserRole;
use App\IdentityAccess\Domain\Entity\User;
use App\Integration\Domain\Entity\TenantAccount;
use App\Shop\Domain\Entity\Shop;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class TenantOwnedShopTest extends ApiTestCase
{
    public function testGerantCreatesShopWithinQuotaAndGetsMembership(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();

        $fixture = $this->seedGerantWithQuota(maxShops: 2, existingShops: 1);
        $headers = $this->authenticateUser($client, $fixture['email'], 'Gerant123!');

        $slug = 'owned-'.substr(md5(uniqid('', true)), 0, 8);
        $client->request('POST', '/api/shops', [], [], $headers, json_encode([
            'name' => 'Boutique Deux',
            'slug' => $slug,
        ]));
        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertSame($slug, $created['data']['slug']);

        $client->request('GET', '/api/shops', [], [], $headers);
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(2, $list['data']);

        $client->request('GET', '/api/me/shops', [], [], $headers);
        self::assertResponseIsSuccessful();
        $meShops = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(2, $meShops['data']);

        $shopId = $created['data']['id'];
        $client->request('POST', '/api/shops/'.$shopId.'/users', [], [], $headers, json_encode([
            'username' => 'caissier1',
            'first_name' => 'Cash',
            'last_name' => 'Ier',
            'roles' => ['caissier'],
        ]));
        self::assertResponseStatusCodeSame(201);
        $userPayload = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($userPayload['generated_password']);
        self::assertContains('caissier', $userPayload['data']['roles']);
        self::assertSame($shopId, $userPayload['data']['shop_id']);

        $client->request('GET', '/api/shops/'.$shopId.'/users', [], [], $headers);
        self::assertResponseIsSuccessful();
        $usersList = json_decode($client->getResponse()->getContent(), true);
        $usernames = array_column($usersList['data'], 'username');
        self::assertContains('caissier1', $usernames);

        $client->request('GET', '/api/shops', [], [], $headers);
        self::assertResponseIsSuccessful();
        $shopsList = json_decode($client->getResponse()->getContent(), true);
        $createdShop = null;
        foreach ($shopsList['data'] as $shop) {
            if ($shop['id'] === $shopId) {
                $createdShop = $shop;
                break;
            }
        }
        self::assertNotNull($createdShop);
        self::assertGreaterThanOrEqual(1, $createdShop['users_count']);
    }

    public function testGerantCannotExceedShopQuota(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();

        $fixture = $this->seedGerantWithQuota(maxShops: 1, existingShops: 1);
        $headers = $this->authenticateUser($client, $fixture['email'], 'Gerant123!');

        $client->request('POST', '/api/shops', [], [], $headers, json_encode([
            'name' => 'Overflow Shop',
            'slug' => 'overflow-'.substr(md5(uniqid('', true)), 0, 8),
        ]));
        self::assertResponseStatusCodeSame(409);
    }

    public function testGerantCannotManageOtherTenantShop(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();

        $fixture = $this->seedGerantWithQuota(maxShops: 2, existingShops: 1);
        $otherShopId = $this->seedForeignTenantShop();
        $headers = $this->authenticateUser($client, $fixture['email'], 'Gerant123!');

        $client->request('GET', '/api/shops/'.$otherShopId.'/users', [], [], $headers);
        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @return array{email: string, shopId: string}
     */
    private function seedGerantWithQuota(int $maxShops, int $existingShops): array
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $suffix = uniqid('', true);
        $email = 'gerant-'.$suffix.'@acme.test';

        $tenant = new TenantAccount('tenant-owned-'.$suffix, [
            'plan' => 'pro',
            'quotas' => ['max_shops' => $maxShops],
        ]);
        $tenant->markProvisioned();
        // Mark snapshot as freshly synced so entitlement pull does not overwrite test quotas.
        $tenant->updateEntitlements([
            'features' => ['stockify.multi_shop'],
            'quotas' => ['max_shops' => $maxShops],
        ]);
        $em->persist($tenant);

        $firstShopId = null;
        for ($i = 0; $i < $existingShops; ++$i) {
            $shop = new Shop('Shop '.$i, 'shop-'.$i.'-'.substr(md5($suffix.$i), 0, 8));
            $shop->setTenantAccountId($tenant->getId());
            $em->persist($shop);
            if (0 === $i) {
                $firstShopId = $shop;
            }
        }

        $gerantUser = new User($email, 'gerant-'.substr(md5($suffix), 0, 8), 'placeholder', 'Gerant', 'Acme');
        $gerantUser->setPasswordHash($hasher->hashPassword($gerantUser, 'Gerant123!'));
        $gerantUser->activate();
        $gerantUser->assignToTenantAccount($tenant->getId());
        if (null !== $firstShopId) {
            $gerantUser->assignToShop($firstShopId->getId());
        }
        $gerantRole = $em->getRepository(Role::class)->findOneBy(['code' => 'gerant']);
        self::assertInstanceOf(Role::class, $gerantRole);
        $em->persist($gerantUser);
        $em->persist(new UserRole($gerantUser, $gerantRole));
        $em->flush();

        return [
            'email' => $email,
            'shopId' => null !== $firstShopId ? (string) $firstShopId->getId() : '',
        ];
    }

    private function seedForeignTenantShop(): string
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $suffix = uniqid('', true);
        $tenant = new TenantAccount('foreign-'.$suffix, ['quotas' => ['max_shops' => 3]]);
        $tenant->markProvisioned();
        $em->persist($tenant);
        $shop = new Shop('Foreign', 'foreign-'.substr(md5($suffix), 0, 8));
        $shop->setTenantAccountId($tenant->getId());
        $em->persist($shop);
        $em->flush();

        return (string) $shop->getId();
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
            'HTTP_AUTHORIZATION' => 'Bearer '.self::extractAccessToken($auth),
            'CONTENT_TYPE' => 'application/json',
        ];
    }
}
