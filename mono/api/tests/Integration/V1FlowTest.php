<?php

namespace App\Tests\Integration;

use App\Catalog\Domain\Entity\UnitOfMeasure;
use App\Catalog\Domain\Repository\UnitOfMeasureRepositoryInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class V1FlowTest extends WebTestCase
{
    private static bool $schemaInitialized = false;

    public function testFullMonoFlowCatalogFifo(): void
    {
        $this->initializeSchema();

        $client = static::createClient();
        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'test@stockify.local',
            'username' => 'testuser',
            'password' => 'password123',
            'first_name' => 'Test',
            'last_name' => 'User',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $auth = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('access_token', $auth);

        $headers = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $auth['access_token'],
            'CONTENT_TYPE' => 'application/json',
        ];

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

    private function initializeSchema(): void
    {
        if (self::$schemaInitialized) {
            return;
        }

        if (is_file(__DIR__ . '/../../var/test.db')) {
            unlink(__DIR__ . '/../../var/test.db');
        }

        self::bootKernel();
        $em = static::getContainer()->get('doctrine')->getManager();
        $tool = new SchemaTool($em);
        $tool->createSchema($em->getMetadataFactory()->getAllMetadata());

        /** @var UnitOfMeasureRepositoryInterface $unitRepo */
        $unitRepo = static::getContainer()->get(UnitOfMeasureRepositoryInterface::class);
        foreach ([['piece', 'Pièce', 0], ['kg', 'Kilogramme', 3], ['liter', 'Litre', 3], ['carton', 'Carton', 0]] as [$code, $label, $decimals]) {
            $unitRepo->save(new UnitOfMeasure($code, $label, $decimals));
        }

        self::$schemaInitialized = true;
        self::ensureKernelShutdown();
    }
}
