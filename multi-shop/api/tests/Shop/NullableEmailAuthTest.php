<?php

namespace App\Tests\Shop;

use App\AccessAudit\Domain\Entity\Role;
use App\AccessAudit\Domain\Entity\UserRole;
use App\IdentityAccess\Domain\Entity\User;
use App\Shop\Domain\Entity\Shop;
use App\Tests\ApiTestCase;
use App\Tests\Support\IntegrationJwtTestHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class NullableEmailAuthTest extends ApiTestCase
{
    public function testCreateShopUserHasNullEmailAndLogsInByUsername(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();

        $shopId = $this->createShopAndPromoteAdmin();
        $adminHeaders = $this->authenticateAdmin($client);

        $username = 'cashier-'.substr(md5(uniqid('', true)), 0, 8);
        $client->request('POST', '/api/shops/'.$shopId.'/users', [], [], $adminHeaders, json_encode([
            'username' => $username,
            'first_name' => 'Cash',
            'last_name' => 'Ier',
            'roles' => ['caissier'],
        ]));
        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertNull($created['data']['email']);
        self::assertSame($username, $created['data']['username']);
        self::assertNotEmpty($created['generated_password']);

        $headers = $this->authenticateWithIdentifier($client, $username, $created['generated_password']);
        $headers['HTTP_X-Shop-Id'] = $shopId;
        $client->request('GET', '/api/products', [], [], $headers);
        self::assertResponseIsSuccessful();
    }

    public function testIntegrationShopWithoutAdminEmailCreatesUsernameOnlyAdmin(): void
    {
        IntegrationJwtTestHelper::ensureKeyPair();
        $this->initializeTestSchema();
        $client = static::createClient();
        $suffix = uniqid('', true);
        $externalAccountId = 'gap-a-'.$suffix;
        $slug = 'gap-a-shop-'.substr(md5($suffix), 0, 8);
        $accountHeaders = IntegrationJwtTestHelper::authHeaders('gap-a-no-email');
        $shopHeaders = IntegrationJwtTestHelper::authHeaders();

        $client->request('POST', '/integration/v1/accounts', [], [], $accountHeaders, json_encode([
            'external_account_id' => $externalAccountId,
            'entitlements' => ['plan' => 'pro', 'max_shops' => 5],
        ]));
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/integration/v1/accounts/'.$externalAccountId.'/shops', [], [], $shopHeaders, json_encode([
            'name' => 'Gap A Shop',
            'slug' => $slug,
        ]));
        self::assertResponseStatusCodeSame(201);
        $shop = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('admin', $shop['data']);
        self::assertNull($shop['data']['admin']['email']);
        self::assertSame($slug.'-admin', $shop['data']['admin']['username']);
        self::assertNotEmpty($shop['data']['admin']['temporary_password']);

        $headers = $this->authenticateWithIdentifier(
            $client,
            $shop['data']['admin']['username'],
            $shop['data']['admin']['temporary_password'],
        );
        $headers['HTTP_X-Shop-Id'] = $shop['data']['id'];
        $client->request('GET', '/api/products', [], [], $headers);
        self::assertResponseIsSuccessful();
    }

    public function testNullifySyntheticEmailClearsEmailForUsernameLogin(): void
    {
        $this->initializeTestSchema();
        static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $suffix = substr(md5(uniqid('', true)), 0, 8);
        $user = new User('synthetic-'.$suffix.'@shop.local', 'synthetic-'.$suffix, 'placeholder', 'Syn', 'Thetic');
        $user->setPasswordHash($hasher->hashPassword($user, 'Synthetic123!'));
        $user->activate();
        $em->persist($user);
        $em->flush();

        $user->nullifySyntheticEmail();
        $em->flush();
        $em->clear();

        $reloaded = $em->getRepository(User::class)->find($user->getId());
        self::assertInstanceOf(User::class, $reloaded);
        self::assertNull($reloaded->getEmail());
        self::assertSame('synthetic-'.$suffix, $reloaded->getUserIdentifier());
    }

    public function testUserWithRealEmailStillLogsInByEmail(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $suffix = substr(md5(uniqid('', true)), 0, 8);
        $email = 'real-'.$suffix.'@example.com';
        $user = new User($email, 'real-'.$suffix, 'placeholder', 'Real', 'User');
        $user->setPasswordHash($hasher->hashPassword($user, 'RealUser123!'));
        $user->activate();
        $gerant = $em->getRepository(Role::class)->findOneBy(['code' => 'gerant']);
        self::assertInstanceOf(Role::class, $gerant);
        $em->persist($user);
        $em->persist(new UserRole($user, $gerant));
        $em->flush();

        $headers = $this->authenticateWithIdentifier($client, $email, 'RealUser123!');
        self::assertArrayHasKey('HTTP_AUTHORIZATION', $headers);
    }

    private function createShopAndPromoteAdmin(): string
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $slug = 'nullable-email-'.substr(md5(uniqid('', true)), 0, 8);
        $shop = new Shop('Nullable Email Shop', $slug);
        $em->persist($shop);

        $admin = $em->getRepository(User::class)->findOneBy(['email' => 'admin-test@stockify.local']);
        self::assertInstanceOf(User::class, $admin);
        if (!$admin->isPlatformOwner()) {
            $admin->promoteToPlatformOwner();
        }
        $em->flush();

        return (string) $shop->getId();
    }

    /** @return array<string, string> */
    private function authenticateWithIdentifier(KernelBrowser $client, string $identifier, string $password): array
    {
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $identifier,
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
