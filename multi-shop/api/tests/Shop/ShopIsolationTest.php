<?php

namespace App\Tests\Shop;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ShopIsolationTest extends WebTestCase
{
    public function testOwnerRequiresShopHeaderForBusinessEndpoints(): void
    {
        $client = static::createClient();
        $token = $this->login($client, 'owner@stockify.local', 'Demo123!');

        $client->request('GET', '/api/products', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);

        self::assertResponseStatusCodeSame(400);
    }

    public function testOwnerCanAccessShopScopedProductsWithHeader(): void
    {
        $client = static::createClient();
        $token = $this->login($client, 'owner@stockify.local', 'Demo123!');
        $shopId = $this->fetchDefaultShopId($client, $token);

        $client->request('GET', '/api/products', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'HTTP_X_SHOP_ID' => $shopId,
        ]);

        self::assertResponseIsSuccessful();
    }

    private function login($client, string $email, string $password): string
    {
        $client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['identifier' => $email, 'password' => $password], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $payload['token'];
    }

    private function fetchDefaultShopId($client, string $token): string
    {
        $client->request('GET', '/api/me/shops', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);

        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $payload['data'][0]['id'];
    }
}
