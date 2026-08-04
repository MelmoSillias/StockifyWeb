<?php

namespace App\Tests\Integration;

use App\Catalog\Domain\Entity\UnitOfMeasure;
use App\Catalog\Domain\Repository\UnitOfMeasureRepositoryInterface;
use App\Finance\Application\Service\FinanceSeedService;
use Doctrine\ORM\Tools\SchemaTool;
use App\Tests\ApiTestCase;

final class FinanceFlowTest extends ApiTestCase
{
    public function testCashPaymentCreatesTransactionOnCaisseAccount(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '10', '100.0000');

        $client->request('POST', '/api/ventes', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Client finance'],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '2', 'unit_price' => '500.00'],
            ],
            'initial_payment' => [
                'amount' => '1000.00',
                'method' => 'cash',
            ],
        ]));
        $this->assertResponseStatusCodeSame(201);
        $vente = json_decode($client->getResponse()->getContent(), true);

        $client->request('GET', '/api/comptes', [], [], $headers);
        $comptes = json_decode($client->getResponse()->getContent(), true);
        $caisse = array_values(array_filter($comptes, static fn ($c) => 'caisse' === $c['type']))[0];
        $this->assertSame('1000.00', $caisse['balance']);

        $client->request('GET', '/api/transactions?compte_id=' . $caisse['id'], [], [], $headers);
        $transactions = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $transactions);
        $this->assertSame('revenu', $transactions[0]['type']);
        $this->assertSame('paiement', $transactions[0]['source_type']);
        $this->assertSame($vente['paiements'][0]['id'], $transactions[0]['source_id']);

        $client->request('POST', "/api/ventes/{$vente['id']}/cancel", [], [], $headers);
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/api/transactions?compte_id=' . $caisse['id'], [], [], $headers);
        $cancelledTransactions = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($cancelledTransactions[0]['is_cancelled']);
    }

    public function testManualTransactionUpdatesAccountBalance(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);

        $client->request('GET', '/api/comptes', [], [], $headers);
        $caisse = array_values(array_filter(
            json_decode($client->getResponse()->getContent(), true),
            static fn ($c) => 'caisse' === $c['type'],
        ))[0];

        $client->request('POST', '/api/transactions', [], [], $headers, json_encode([
            'compte_id' => $caisse['id'],
            'type' => 'depense',
            'amount' => '150.00',
            'label' => 'Achat fournitures',
            'description' => 'Test manuel',
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/comptes/' . $caisse['id'], [], [], $headers);
        $updated = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('-150.00', $updated['balance']);
    }

    /**
     * @param array<string, string> $headers
     */
    private function createVariantWithStock($client, array $headers, string $quantity, string $unitCost): string
    {
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
