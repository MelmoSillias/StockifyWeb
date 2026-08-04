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
}
