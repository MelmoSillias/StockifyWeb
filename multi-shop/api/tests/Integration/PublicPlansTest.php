<?php

namespace App\Tests\Integration;

use App\Tests\ApiTestCase;
use App\Tests\Support\StubControlPlaneClient;

final class PublicPlansTest extends ApiTestCase
{
    public function testPublicPlansEndpointReturnsControlPlanePlans(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();

        $client->request('GET', '/api/public/plans?application=stockify');
        $this->assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $payload['items']);
        $this->assertSame('starter', $payload['items'][0]['code']);
        $this->assertSame('pro', $payload['items'][1]['code']);
    }
}
