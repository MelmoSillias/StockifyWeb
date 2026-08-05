<?php

namespace App\Tests\Integration;

use App\Tests\ApiTestCase;
use App\Tests\Support\IntegrationJwtTestHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class IntegrationApiTest extends ApiTestCase
{
    public function testHealthIsPublic(): void
    {
        IntegrationJwtTestHelper::ensureKeyPair();
        $this->initializeTestSchema();
        $client = static::createClient();

        $client->request('GET', '/integration/v1/health');
        $this->assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('ok', $payload['status']);
        $this->assertTrue($payload['integration_enabled']);
    }

    public function testCapabilitiesRequiresIntegrationJwt(): void
    {
        IntegrationJwtTestHelper::ensureKeyPair();
        $this->initializeTestSchema();
        $client = static::createClient();

        $client->request('GET', '/integration/v1/capabilities');
        $this->assertResponseStatusCodeSame(401);

        $client->request('GET', '/integration/v1/capabilities', [], [], IntegrationJwtTestHelper::authHeaders());
        $this->assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('1.0', $payload['data']['version']);
    }

    public function testTokenIssuedForAnotherAudienceIsRejected(): void
    {
        IntegrationJwtTestHelper::ensureKeyPair();
        $this->initializeTestSchema();
        $client = static::createClient();

        $foreignToken = IntegrationJwtTestHelper::createToken(audience: 'dentalsoft');
        $client->request('GET', '/integration/v1/capabilities', [], [], IntegrationJwtTestHelper::authHeaders(null, $foreignToken));
        $this->assertResponseStatusCodeSame(401);
    }

    public function testTokenFromUnknownIssuerIsRejected(): void
    {
        IntegrationJwtTestHelper::ensureKeyPair();
        $this->initializeTestSchema();
        $client = static::createClient();

        $foreignToken = IntegrationJwtTestHelper::createToken(issuer: 'rogue-control-plane');
        $client->request('GET', '/integration/v1/capabilities', [], [], IntegrationJwtTestHelper::authHeaders(null, $foreignToken));
        $this->assertResponseStatusCodeSame(401);
    }

    public function testReadOnlyTokenCannotProvisionAccounts(): void
    {
        IntegrationJwtTestHelper::ensureKeyPair();
        $this->initializeTestSchema();
        $client = static::createClient();

        $readOnly = IntegrationJwtTestHelper::createToken(scopes: 'integration:read');

        $client->request('GET', '/integration/v1/capabilities', [], [], IntegrationJwtTestHelper::authHeaders(null, $readOnly));
        $this->assertResponseIsSuccessful();

        $client->request('POST', '/integration/v1/accounts', [], [], IntegrationJwtTestHelper::authHeaders(null, $readOnly), json_encode([
            'external_account_id' => 'scope-test',
        ]));
        $this->assertResponseStatusCodeSame(403);
    }

    public function testProvisionAccountAndLifecycle(): void
    {
        IntegrationJwtTestHelper::ensureKeyPair();
        $this->initializeTestSchema();
        $client = static::createClient();
        $headers = IntegrationJwtTestHelper::authHeaders('provision-acme-001');

        $client->request('POST', '/integration/v1/accounts', [], [], $headers, json_encode([
            'external_account_id' => 'acme-corp',
            'entitlements' => ['plan' => 'pro', 'max_shops' => 5],
        ]));
        $this->assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('acme-corp', $created['data']['external_account_id']);
        $this->assertSame('active', $created['data']['status']);

        $client->request('POST', '/integration/v1/accounts', [], [], $headers, json_encode([
            'external_account_id' => 'acme-corp',
            'entitlements' => ['plan' => 'pro', 'max_shops' => 5],
        ]));
        $this->assertResponseIsSuccessful();
        $idempotent = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($created['data']['id'], $idempotent['data']['id']);

        $client->request('GET', '/integration/v1/accounts/acme-corp', [], [], IntegrationJwtTestHelper::authHeaders());
        $this->assertResponseIsSuccessful();

        $client->request('PATCH', '/integration/v1/accounts/acme-corp/entitlements', [], [], IntegrationJwtTestHelper::authHeaders(), json_encode([
            'entitlements' => ['plan' => 'enterprise', 'max_shops' => 10],
        ]));
        $this->assertResponseIsSuccessful();
        $updated = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('enterprise', $updated['data']['entitlements']['plan']);

        $client->request('POST', '/integration/v1/accounts/acme-corp/shops', [], [], IntegrationJwtTestHelper::authHeaders(), json_encode([
            'name' => 'Acme Main',
            'slug' => 'acme-main',
            'currency' => 'XOF',
            'admin_email' => 'admin@acme.test',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $shop = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('acme-main', $shop['data']['slug']);
        $this->assertSame($created['data']['id'], $shop['data']['tenant_account_id']);
        $this->assertSame('admin@acme.test', $shop['data']['admin']['email']);
        $this->assertNotEmpty($shop['data']['admin']['temporary_password']);

        $client->request('GET', '/integration/v1/accounts/acme-corp/usage', [], [], IntegrationJwtTestHelper::authHeaders());
        $this->assertResponseIsSuccessful();
        $usage = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $usage['data']['shops_count']);

        $client->request('POST', '/integration/v1/accounts/acme-corp/suspend', [], [], IntegrationJwtTestHelper::authHeaders());
        $this->assertResponseIsSuccessful();
        $suspended = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('suspended', $suspended['data']['status']);

        $this->assertShopAccessBlockedWhenTenantSuspended($client, $shop['data']['id']);

        $client->request('POST', '/integration/v1/accounts/acme-corp/activate', [], [], IntegrationJwtTestHelper::authHeaders());
        $this->assertResponseIsSuccessful();

        $client->request('DELETE', '/integration/v1/accounts/acme-corp', [], [], IntegrationJwtTestHelper::authHeaders());
        $this->assertResponseStatusCodeSame(409);

        $client->request('DELETE', '/integration/v1/accounts/unknown', [], [], IntegrationJwtTestHelper::authHeaders());
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreateShopIsIdempotentByKeyAndSlug(): void
    {
        IntegrationJwtTestHelper::ensureKeyPair();
        $this->initializeTestSchema();
        $client = static::createClient();

        $client->request('POST', '/integration/v1/accounts', [], [], IntegrationJwtTestHelper::authHeaders('idem-shop-account'), json_encode([
            'external_account_id' => 'idem-shop-corp',
            'entitlements' => ['max_shops' => 5],
        ]));
        $this->assertResponseStatusCodeSame(201);

        $headers = IntegrationJwtTestHelper::authHeaders('idem-shop-corp-initial-shop');
        $payload = json_encode([
            'name' => 'Idem Shop',
            'slug' => 'idem-shop',
            'admin_email' => 'admin@idem-shop.test',
        ]);

        $client->request('POST', '/integration/v1/accounts/idem-shop-corp/shops', [], [], $headers, $payload);
        $this->assertResponseStatusCodeSame(201);
        $first = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/integration/v1/accounts/idem-shop-corp/shops', [], [], $headers, $payload);
        $this->assertResponseStatusCodeSame(201);
        $cached = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($first['data']['id'], $cached['data']['id']);

        $client->request('POST', '/integration/v1/accounts/idem-shop-corp/shops', [], [], IntegrationJwtTestHelper::authHeaders(), $payload);
        $this->assertResponseStatusCodeSame(201);
        $bySlug = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($first['data']['id'], $bySlug['data']['id']);
    }

    private function assertShopAccessBlockedWhenTenantSuspended(KernelBrowser $client, string $shopId): void
    {
        $adminHeaders = $this->authenticateAdmin($client);
        $adminHeaders['HTTP_X-Shop-Id'] = $shopId;

        $client->request('GET', '/api/categories', [], [], $adminHeaders);
        $this->assertResponseStatusCodeSame(403);
    }
}
