<?php

namespace App\Tests\Integration;

use App\Tests\ApiTestCase;
use App\Tests\Support\StubControlPlaneClient;

final class PublicSignupTest extends ApiTestCase
{
    public function testPublicSignupForcesStarterPlan(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();

        /** @var StubControlPlaneClient $controlPlane */
        $controlPlane = static::getContainer()->get(StubControlPlaneClient::class);

        $client->request('POST', '/api/public/signup', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'accountName' => 'Acme Shop',
            'accountSlug' => 'acme-shop',
            'billingEmail' => 'owner@acme.test',
            'adminEmail' => 'owner@acme.test',
            'planCode' => 'pro',
        ]));

        $this->assertResponseStatusCodeSame(201);

        $payload = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('starter', $payload['subscription']['planCode']);
        $this->assertSame('starter', $controlPlane->lastSignupPayload['planCode']);
        $this->assertSame('acme-shop', $controlPlane->lastSignupPayload['accountSlug']);
        $this->assertFalse($payload['bindingPending']);
        $this->assertSame(1, $controlPlane->completeSignupCalls);
    }

    public function testPublicSignupLinksOwnerIdentityId(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();

        $client->request('POST', '/api/public/signup', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'Ida',
            'lastName' => 'Owner',
            'accountName' => 'Identity Owner Shop',
            'accountSlug' => 'identity-owner-shop',
            'billingEmail' => 'owner@identity-owner.test',
            'adminEmail' => 'owner@identity-owner.test',
            'adminPassword' => 'Password123!',
            'planCode' => 'starter',
        ]));

        $this->assertResponseStatusCodeSame(201);

        $users = static::getContainer()->get(\App\IdentityAccess\Domain\Repository\UserRepositoryInterface::class);
        $owner = $users->findByEmail('owner@identity-owner.test');
        $this->assertNotNull($owner);
        $this->assertNotNull($owner->getIdentityId());
        $this->assertSame('Ida', $owner->getFirstName());
        $this->assertSame('Owner', $owner->getLastName());
    }

    public function testPublicSignupPersistsIdentityAndShopProfile(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();

        $client->request('POST', '/api/public/signup', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'Aïcha',
            'lastName' => 'Diallo',
            'phone' => '+22790000000',
            'accountName' => 'Profile Shop',
            'accountSlug' => 'profile-shop',
            'shopPhone' => '+22720000000',
            'shopAddress' => 'Niamey, Niger',
            'billingEmail' => 'owner@profile-shop.test',
            'adminEmail' => 'owner@profile-shop.test',
            'adminPassword' => 'Password123!',
            'planCode' => 'starter',
        ]));

        $this->assertResponseStatusCodeSame(201);

        $users = static::getContainer()->get(\App\IdentityAccess\Domain\Repository\UserRepositoryInterface::class);
        $owner = $users->findByEmail('owner@profile-shop.test');
        $this->assertNotNull($owner);
        $this->assertSame('Aïcha', $owner->getFirstName());
        $this->assertSame('Diallo', $owner->getLastName());
        $this->assertSame('+22790000000', $owner->getPhone());

        $shops = static::getContainer()->get(\App\Shop\Domain\Repository\ShopRepositoryInterface::class);
        $shop = $shops->findBySlug('profile-shop');
        $this->assertNotNull($shop);
        $this->assertSame('+22720000000', $shop->getPhone());
        $this->assertSame('Niamey, Niger', $shop->getAddress());
    }

    public function testPublicSignupRetriesCompleteSignupThenSucceeds(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();

        /** @var StubControlPlaneClient $controlPlane */
        $controlPlane = static::getContainer()->get(StubControlPlaneClient::class);
        $controlPlane->failCompleteSignupTimes = 2;

        $client->request('POST', '/api/public/signup', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'Retry',
            'lastName' => 'Bind',
            'accountName' => 'Retry Bind Shop',
            'accountSlug' => 'retry-bind-shop',
            'billingEmail' => 'owner@retry-bind.test',
            'adminEmail' => 'owner@retry-bind.test',
            'adminPassword' => 'Password123!',
            'planCode' => 'starter',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $payload = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($payload['bindingPending']);
        $this->assertNotNull($payload['tenantBinding']);
        $this->assertSame(3, $controlPlane->completeSignupCalls);
    }

    public function testPublicSignupReturnsBindingPendingWhenCompleteKeepsFailing(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();

        /** @var StubControlPlaneClient $controlPlane */
        $controlPlane = static::getContainer()->get(StubControlPlaneClient::class);
        $controlPlane->failCompleteSignupTimes = 10;

        $client->request('POST', '/api/public/signup', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'Pending',
            'lastName' => 'Bind',
            'accountName' => 'Pending Bind Shop',
            'accountSlug' => 'pending-bind-shop',
            'billingEmail' => 'owner@pending-bind.test',
            'adminEmail' => 'owner@pending-bind.test',
            'adminPassword' => 'Password123!',
            'planCode' => 'starter',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $payload = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($payload['bindingPending']);
        $this->assertNull($payload['tenantBinding']);
        $this->assertNotEmpty($payload['shopCredentials']['email']);
        $this->assertSame(3, $controlPlane->completeSignupCalls);
    }

    public function testPublicSignupValidatesRequiredFields(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();

        $client->request('POST', '/api/public/signup', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'accountSlug' => 'missing-name',
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testPublicSignupRequiresFirstAndLastName(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();

        $client->request('POST', '/api/public/signup', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'accountName' => 'No Identity Shop',
            'accountSlug' => 'no-identity-shop',
            'billingEmail' => 'owner@no-identity.test',
            'adminEmail' => 'owner@no-identity.test',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $payload = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Field "firstName" is required.', $payload['error']);
    }
}
