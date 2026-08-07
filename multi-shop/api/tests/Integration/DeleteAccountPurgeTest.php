<?php

namespace App\Tests\Integration;

use App\IdentityAccess\Domain\Entity\User;
use App\Integration\Domain\Entity\TenantAccount;
use App\Shop\Domain\Entity\Shop;
use App\Tests\ApiTestCase;
use App\Tests\Support\IntegrationJwtTestHelper;
use Doctrine\ORM\EntityManagerInterface;

final class DeleteAccountPurgeTest extends ApiTestCase
{
    public function testGuardModeRefusesWhenShopsExist(): void
    {
        IntegrationJwtTestHelper::ensureKeyPair();
        $this->initializeTestSchema();
        $client = static::createClient();
        $accountHeaders = IntegrationJwtTestHelper::authHeaders('purge-guard');
        $shopHeaders = IntegrationJwtTestHelper::authHeaders();

        $suffix = uniqid('', true);
        $externalId = 'guard-'.$suffix;
        $this->provisionWithShop($client, $accountHeaders, $shopHeaders, $externalId, 'guard-shop-'.substr(md5($suffix), 0, 8));

        $client->request('DELETE', '/integration/v1/accounts/'.$externalId.'?mode=guard', [], [], $accountHeaders);
        self::assertResponseStatusCodeSame(409);
    }

    public function testPurgeModeForbiddenWhenFlagDisabled(): void
    {
        IntegrationJwtTestHelper::ensureKeyPair();
        putenv('TENANT_PURGE_ENABLED=0');
        $_ENV['TENANT_PURGE_ENABLED'] = '0';
        $_SERVER['TENANT_PURGE_ENABLED'] = '0';

        $this->initializeTestSchema();
        $client = static::createClient();
        $accountHeaders = IntegrationJwtTestHelper::authHeaders('purge-flag-off');
        $shopHeaders = IntegrationJwtTestHelper::authHeaders();

        $suffix = uniqid('', true);
        $externalId = 'flag-off-'.$suffix;
        $this->provisionWithShop($client, $accountHeaders, $shopHeaders, $externalId, 'flag-off-'.substr(md5($suffix), 0, 8));

        $client->request('DELETE', '/integration/v1/accounts/'.$externalId.'?mode=purge', [], [], $accountHeaders);
        self::assertResponseStatusCodeSame(403);
    }

    public function testPurgeModeRemovesTenantShopsAndUsers(): void
    {
        IntegrationJwtTestHelper::ensureKeyPair();
        putenv('TENANT_PURGE_ENABLED=1');
        $_ENV['TENANT_PURGE_ENABLED'] = '1';
        $_SERVER['TENANT_PURGE_ENABLED'] = '1';

        $this->initializeTestSchema();
        self::ensureKernelShutdown();
        $client = static::createClient();
        $accountHeaders = IntegrationJwtTestHelper::authHeaders('purge-ok');
        $shopHeaders = IntegrationJwtTestHelper::authHeaders();

        $suffix = uniqid('', true);
        $externalId = 'purge-ok-'.$suffix;
        $slug = 'purge-ok-'.substr(md5($suffix), 0, 8);
        $shopPayload = $this->provisionWithShop($client, $accountHeaders, $shopHeaders, $externalId, $slug, 'admin-'.$suffix.'@purge.test');
        $shopId = $shopPayload['data']['id'];

        $client->request('DELETE', '/integration/v1/accounts/'.$externalId.'?mode=purge', [], [], $accountHeaders);
        self::assertResponseStatusCodeSame(202);
        $body = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('purge', $body['deletion_receipt']['mode']);
        self::assertSame(1, $body['deletion_receipt']['shops_purged']);
        self::assertGreaterThanOrEqual(1, $body['deletion_receipt']['users_purged']);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        self::assertNull($em->getRepository(TenantAccount::class)->findOneBy(['externalAccountId' => $externalId]));
        self::assertNull($em->getRepository(Shop::class)->find($shopId));
        self::assertNull($em->getRepository(User::class)->findOneBy(['email' => 'admin-'.$suffix.'@purge.test']));
    }

    /** @return array<string, mixed> */
    private function provisionWithShop(
        \Symfony\Bundle\FrameworkBundle\KernelBrowser $client,
        array $accountHeaders,
        array $shopHeaders,
        string $externalId,
        string $slug,
        ?string $adminEmail = null,
    ): array {
        $client->request('POST', '/integration/v1/accounts', [], [], $accountHeaders, json_encode([
            'external_account_id' => $externalId,
            'entitlements' => ['plan' => 'pro', 'max_shops' => 5],
        ]));
        self::assertResponseStatusCodeSame(201);

        $payload = [
            'name' => 'Purge Shop',
            'slug' => $slug,
        ];
        if (null !== $adminEmail) {
            $payload['admin_email'] = $adminEmail;
        }

        $client->request('POST', '/integration/v1/accounts/'.$externalId.'/shops', [], [], $shopHeaders, json_encode($payload));
        self::assertResponseStatusCodeSame(201);

        return json_decode($client->getResponse()->getContent(), true);
    }
}
