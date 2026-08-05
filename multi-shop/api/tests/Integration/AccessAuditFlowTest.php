<?php

namespace App\Tests\Integration;

use App\Tests\ApiTestCase;

final class AccessAuditFlowTest extends ApiTestCase
{
    public function testMeReturnsPermissionsForAdmin(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);

        $client->request('GET', '/api/me', [], [], $headers);
        $this->assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('permissions', $payload);
        $this->assertArrayHasKey('features', $payload);
        $this->assertIsArray($payload['features']);
        $this->assertContains('access.users.view', $payload['permissions']);
    }

    public function testRegisterRequiresAuthentication(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();

        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'new@stockify.local',
            'username' => 'newuser',
            'password' => 'password123',
            'first_name' => 'New',
            'last_name' => 'User',
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAdminCanCreateCategory(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);

        $client->request('POST', '/api/categories', [], [], $headers, json_encode(['name' => 'Test']));
        $this->assertResponseStatusCodeSame(201);
    }
}
