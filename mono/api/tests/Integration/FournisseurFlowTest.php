<?php

namespace App\Tests\Integration;

use App\Tests\ApiTestCase;

final class FournisseurFlowTest extends ApiTestCase
{
    public function testFournisseurCrudAndManualDebtPaymentFlow(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);

        $client->request('POST', '/api/fournisseurs', [], [], $headers, json_encode([
            'name' => 'Fournisseur Test',
            'phone' => '0600000000',
            'email' => 'fournisseur@test.local',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $fournisseur = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/dettes-fournisseur', [], [], $headers, json_encode([
            'fournisseur_id' => $fournisseur['id'],
            'total_amount' => '500.00',
            'label' => 'Dette manuelle test',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $dette = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('500.00', $dette['balance']);

        $client->request('POST', '/api/paiements-fournisseur', [], [], $headers, json_encode([
            'dette_fournisseur_id' => $dette['id'],
            'amount' => '500.00',
            'method' => 'cash',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $paiement = json_decode($client->getResponse()->getContent(), true);

        $client->request('GET', '/api/dettes-fournisseur/' . $dette['id'], [], [], $headers);
        $detteUpdated = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('0.00', $detteUpdated['balance']);
        $this->assertSame('soldee', $detteUpdated['statut']);

        $client->request('GET', '/api/comptes', [], [], $headers);
        $caisse = array_values(array_filter(
            json_decode($client->getResponse()->getContent(), true),
            static fn ($c) => 'caisse' === $c['type'],
        ))[0];
        $this->assertSame('-500.00', $caisse['balance']);

        $client->request('POST', '/api/paiements-fournisseur/' . $paiement['id'] . '/cancel', [], [], $headers);
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/api/dettes-fournisseur/' . $dette['id'], [], [], $headers);
        $detteReopened = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('500.00', $detteReopened['balance']);
        $this->assertSame('en_cours', $detteReopened['statut']);
    }

    public function testPurchaseOrderReceiveCreatesStockAndDebt(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariant($client, $headers);

        $client->request('POST', '/api/fournisseurs', [], [], $headers, json_encode([
            'name' => 'Fournisseur Achat',
        ]));
        $fournisseur = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/commandes-fournisseur', [], [], $headers, json_encode([
            'fournisseur_id' => $fournisseur['id'],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '5', 'unit_cost' => '100.00'],
            ],
        ]));
        $this->assertResponseStatusCodeSame(201);
        $commande = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('500.00', $commande['total_amount']);

        $client->request('POST', '/api/commandes-fournisseur/' . $commande['id'] . '/confirm', [], [], $headers);
        $this->assertResponseIsSuccessful();

        $client->request('POST', '/api/commandes-fournisseur/' . $commande['id'] . '/recevoir', [], [], $headers, json_encode([
            'paid_amount' => '200.00',
            'method' => 'cash',
        ]));
        $this->assertResponseIsSuccessful();
        $received = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('recue', $received['status']);

        $client->request('GET', '/api/variants/' . $variantId . '/stock', [], [], $headers);
        $stock = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('5.000', $stock['available']);

        $client->request('GET', '/api/fournisseurs/' . $fournisseur['id'] . '/dettes', [], [], $headers);
        $dettes = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $dettes);
        $this->assertSame('300.00', $dettes[0]['balance']);
    }

    /**
     * @param array<string, string> $headers
     */
    private function createVariant($client, array $headers): string
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
            'default_price' => '150.00',
        ]));
        $variant = json_decode($client->getResponse()->getContent(), true);

        return $variant['id'];
    }
}
