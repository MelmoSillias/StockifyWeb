<?php

namespace App\Tests\Integration;

use App\Tests\ApiTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class V1FlowTest extends ApiTestCase
{
    private static bool $schemaInitialized = false;

    public function testFullMonoFlowCatalogFifo(): void
    {
        $this->initializeTestSchema();

        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);

        $client->request('GET', '/api/units-of-measure');
        $this->assertResponseIsSuccessful();
        $units = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEmpty($units);
        $unitId = $units[0]['id'];

        $client->request('POST', '/api/categories', [], [], $headers, json_encode([
            'name' => 'Boissons',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $category = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/products', [], [], $headers, json_encode([
            'name' => 'Eau minérale',
            'category_id' => $category['id'],
        ]));
        $this->assertResponseStatusCodeSame(201);
        $product = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', "/api/products/{$product['id']}/variants", [], [], $headers, json_encode([
            'sku' => 'EAU-1L-' . uniqid(),
            'unit_of_measure_id' => $unitId,
            'sale_mode' => 'unit',
            'default_price' => '500.00',
            'alert_threshold' => '10',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $variant = json_decode($client->getResponse()->getContent(), true);
        $variantId = $variant['id'];

        $client->request('POST', "/api/variants/{$variantId}/lots", [], [], $headers, json_encode([
            'quantity' => '100',
            'unit_cost' => '300.0000',
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', "/api/variants/{$variantId}/lots", [], [], $headers, json_encode([
            'quantity' => '50',
            'unit_cost' => '320.0000',
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', "/api/variants/{$variantId}/stock", [], [], $headers);
        $this->assertResponseIsSuccessful();
        $stock = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(150, (float) $stock['available']);

        $client->request('POST', "/api/variants/{$variantId}/stock-out", [], [], $headers, json_encode([
            'quantity' => '30',
            'reason' => 'Test FIFO',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $movement = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $movement['allocations']);
        $this->assertEquals(30, (float) $movement['allocations'][0]['quantity']);

        $client->request('GET', "/api/variants/{$variantId}/stock", [], [], $headers);
        $stockAfter = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(120, (float) $stockAfter['available']);
    }
}
