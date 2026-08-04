<?php

namespace App\Tests\Integration;

use App\Tests\ApiTestCase;

final class AnalyticsFlowTest extends ApiTestCase
{
    public function testAnalyticsOverviewReturnsSalesComparisonAndProjection(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '10', '100.0000');

        $client->request('POST', '/api/ventes', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Client analytics'],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '2', 'unit_price' => '500.00'],
            ],
            'initial_payment' => [
                'amount' => '1000.00',
                'method' => 'cash',
            ],
        ]));
        $this->assertResponseStatusCodeSame(201);

        $today = new \DateTimeImmutable('today');
        $from = $today->modify('-6 days')->format('Y-m-d');
        $to = $today->format('Y-m-d');

        $client->request('GET', '/api/analytics/overview', [
            'from' => $from,
            'to' => $to,
            'compare' => 'true',
        ], [], $headers);
        $this->assertResponseIsSuccessful();

        $overview = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('sales', $overview);
        $this->assertSame(1, $overview['sales']['count']);
        $this->assertSame('1000.00', $overview['sales']['net_amount']);
        $this->assertArrayHasKey('projection', $overview);
        $this->assertArrayHasKey('projected_amount', $overview['projection']);
        $this->assertArrayHasKey('comparison', $overview);
        $this->assertArrayHasKey('sales', $overview['comparison']);
    }

    public function testAnalyticsSalesEndpointReturnsTrendAndTopProducts(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '5', '50.0000');

        $client->request('POST', '/api/ventes', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Test sales analytics'],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '1', 'unit_price' => '250.00'],
            ],
            'initial_payment' => [
                'amount' => '250.00',
                'method' => 'cash',
            ],
        ]));
        $this->assertResponseStatusCodeSame(201);

        $today = new \DateTimeImmutable('today');
        $from = $today->modify('-6 days')->format('Y-m-d');
        $to = $today->format('Y-m-d');

        $client->request('GET', '/api/analytics/sales', [
            'from' => $from,
            'to' => $to,
        ], [], $headers);
        $this->assertResponseIsSuccessful();

        $sales = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('trend', $sales);
        $this->assertNotEmpty($sales['trend']);
        $this->assertArrayHasKey('top_products', $sales);
        $this->assertGreaterThanOrEqual(1, count($sales['top_products']));
        $this->assertGreaterThanOrEqual(1, $sales['summary']['count']);
        $this->assertGreaterThanOrEqual('250.00', $sales['summary']['net_amount']);
    }

    public function testAnalyticsRequiresPermission(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();

        $today = new \DateTimeImmutable('today');
        $from = $today->modify('-6 days')->format('Y-m-d');
        $to = $today->format('Y-m-d');

        $client->request('GET', '/api/analytics/overview', [
            'from' => $from,
            'to' => $to,
        ]);
        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * @param array<string, string> $headers
     */
    private function createVariantWithStock(
        \Symfony\Bundle\FrameworkBundle\KernelBrowser $client,
        array $headers,
        string $quantity,
        string $unitCost,
    ): string {
        $client->request('GET', '/api/units-of-measure', [], [], $headers);
        $unitId = json_decode($client->getResponse()->getContent(), true)[0]['id'];

        $client->request('POST', '/api/categories', [], [], $headers, json_encode(['name' => 'Cat-' . uniqid()]));
        $category = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/products', [], [], $headers, json_encode([
            'name' => 'Produit-' . uniqid(),
            'category_id' => $category['id'],
        ]));
        $product = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', "/api/products/{$product['id']}/variants", [], [], $headers, json_encode([
            'sku' => 'SKU-' . uniqid(),
            'unit_of_measure_id' => $unitId,
            'sale_mode' => 'unit',
            'default_price' => '500.00',
        ]));
        $variant = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', "/api/variants/{$variant['id']}/lots", [], [], $headers, json_encode([
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
        ]));
        $this->assertResponseStatusCodeSame(201);

        return $variant['id'];
    }
}
