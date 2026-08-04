<?php

namespace App\Tests\Integration;

use App\Tests\ApiTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class CommerceFlowTest extends ApiTestCase
{
    public function testVenteGeneratesInvoiceAndDecrementsStock(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '100', '300.0000');

        $client->request('POST', '/api/ventes', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Client comptoir'],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '5', 'unit_price' => '500.00'],
            ],
        ]));
        $this->assertResponseStatusCodeSame(201);
        $vente = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('2500.00', $vente['total_amount']);
        $this->assertSame('impaye', $vente['payment_status']);
        $this->assertSame('0.00', $vente['paid_amount']);
        $this->assertSame('2500.00', $vente['balance']);
        $this->assertCount(1, $vente['lines']);
        $this->assertNotNull($vente['facture']);

        $client->request('GET', '/api/factures', [], [], $headers);
        $this->assertResponseIsSuccessful();
        $factures = json_decode($client->getResponse()->getContent(), true);
        $matching = array_values(array_filter($factures, static fn ($f) => $f['vente_id'] === $vente['id']));
        $this->assertCount(1, $matching, 'A single invoice must be auto-generated for the sale.');
        $this->assertSame('2500.00', $matching[0]['total_amount']);
        $this->assertTrue($matching[0]['is_creance']);
        $this->assertNull($matching[0]['credit_closed_at']);
        $this->assertTrue($vente['facture']['is_creance']);

        $client->request('GET', "/api/variants/{$variantId}/stock", [], [], $headers);
        $stock = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(95, (float) $stock['available'], 'Stock must be decremented by the sold quantity.');
    }

    public function testCommandeConfirmationCreatesInvoiceAndCancellationRestocks(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '50', '300.0000');

        $client->request('POST', '/api/commandes', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Client B'],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '8', 'unit_price' => '400.00'],
            ],
        ]));
        $this->assertResponseStatusCodeSame(201);
        $commande = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('initiee', $commande['status']);

        $client->request('POST', "/api/commandes/{$commande['id']}/confirm", [], [], $headers, json_encode([
            'delivery_date' => '2026-08-15',
        ]));
        $this->assertResponseIsSuccessful();
        $confirmed = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('confirmee', $confirmed['status']);
        $this->assertSame('2026-08-15', $confirmed['delivery_date']);

        $client->request('GET', '/api/factures', [], [], $headers);
        $factures = json_decode($client->getResponse()->getContent(), true);
        $matching = array_values(array_filter($factures, static fn ($f) => $f['commande_id'] === $commande['id']));
        $this->assertCount(1, $matching, 'Confirming an order must generate an invoice.');

        $client->request('GET', "/api/variants/{$variantId}/stock", [], [], $headers);
        $this->assertEquals(50, (float) json_decode($client->getResponse()->getContent(), true)['available'], 'Stock must not move on confirmation anymore.');

        $client->request('POST', "/api/commandes/{$commande['id']}/bons-livraison", [], [], $headers, json_encode([
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '5'],
            ],
        ]));
        $this->assertResponseStatusCodeSame(201);
        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('envoye', $result['bon']['status']);
        $this->assertSame('partiellement_livree', $result['commande_status']);

        $client->request('GET', "/api/variants/{$variantId}/stock", [], [], $headers);
        $this->assertEquals(45, (float) json_decode($client->getResponse()->getContent(), true)['available']);

        $client->request('POST', "/api/commandes/{$commande['id']}/cancel", [], [], $headers);
        $this->assertResponseIsSuccessful();

        $client->request('GET', "/api/variants/{$variantId}/stock", [], [], $headers);
        $this->assertEquals(50, (float) json_decode($client->getResponse()->getContent(), true)['available'], 'Cancelling a partially delivered order must restock shipped quantities only.');
    }

    public function testAcompteOnOrderTracksDeposit(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '30', '200.0000');

        $client->request('POST', '/api/commandes', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Client C'],
            'lines' => [['variant_id' => $variantId, 'quantity' => '2', 'unit_price' => '1000.00']],
        ]));
        $commande = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/paiements', [], [], $headers, json_encode([
            'commande_id' => $commande['id'],
            'amount' => '500.00',
            'method' => 'cash',
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', "/api/commandes/{$commande['id']}", [], [], $headers);
        $refreshed = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('500.00', $refreshed['deposit_received']);
    }

    public function testVentePaymentRejectsAmountAboveBalance(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '20', '300.0000');

        $client->request('POST', '/api/ventes', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Client paiement'],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '2', 'unit_price' => '500.00'],
            ],
        ]));
        $vente = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/paiements', [], [], $headers, json_encode([
            'facture_id' => $vente['facture']['id'],
            'amount' => '500.00',
            'method' => 'cash',
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/paiements', [], [], $headers, json_encode([
            'facture_id' => $vente['facture']['id'],
            'amount' => '600.00',
            'method' => 'cash',
        ]));
        $this->assertResponseStatusCodeSame(400);
    }

    public function testVenteCancellationCreatesAvoirCancelsPaymentsAndRestocks(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '40', '300.0000');

        $client->request('POST', '/api/ventes', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Client annulation'],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '6', 'unit_price' => '500.00'],
            ],
        ]));
        $vente = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/paiements', [], [], $headers, json_encode([
            'facture_id' => $vente['facture']['id'],
            'amount' => '1000.00',
            'method' => 'cash',
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', "/api/variants/{$variantId}/stock", [], [], $headers);
        $this->assertEquals(34, (float) json_decode($client->getResponse()->getContent(), true)['available']);

        $client->request('POST', "/api/ventes/{$vente['id']}/cancel", [], [], $headers);
        $this->assertResponseIsSuccessful();
        $cancelled = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotNull($cancelled['cancelled_at']);
        $this->assertSame('annulee', $cancelled['payment_status']);
        $this->assertNotNull($cancelled['avoir']);
        $this->assertSame('3000.00', $cancelled['avoir']['total_amount']);
        $this->assertTrue($cancelled['paiements'][0]['is_cancelled']);

        $client->request('GET', "/api/variants/{$variantId}/stock", [], [], $headers);
        $this->assertEquals(40, (float) json_decode($client->getResponse()->getContent(), true)['available']);

        $client->request('POST', "/api/ventes/{$vente['id']}/cancel", [], [], $headers);
        $this->assertResponseStatusCodeSame(409);
    }

    public function testFullPaymentAtCheckoutDoesNotMarkCreance(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '20', '300.0000');

        $client->request('POST', '/api/ventes', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Client comptant'],
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
        $this->assertSame('paye', $vente['payment_status']);
        $this->assertFalse($vente['facture']['is_creance']);
        $this->assertNull($vente['facture']['credit_closed_at']);
    }

    public function testPartialPaymentAtCheckoutMarksCreance(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '20', '300.0000');
        $registeredClient = $this->createClientEntity($client, $headers);

        $client->request('POST', '/api/ventes', [], [], $headers, json_encode([
            'acheteur' => ['client_id' => $registeredClient['id']],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '2', 'unit_price' => '500.00'],
            ],
            'initial_payment' => [
                'amount' => '400.00',
                'method' => 'cash',
            ],
        ]));
        $this->assertResponseStatusCodeSame(201);
        $vente = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('partiellement_paye', $vente['payment_status']);
        $this->assertTrue($vente['facture']['is_creance']);
        $this->assertNull($vente['facture']['credit_closed_at']);
        $this->assertSame('600.00', $vente['balance']);
    }

    public function testFinalPaymentClosesCreance(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '20', '300.0000');
        $registeredClient = $this->createClientEntity($client, $headers);

        $client->request('POST', '/api/ventes', [], [], $headers, json_encode([
            'acheteur' => ['client_id' => $registeredClient['id']],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '2', 'unit_price' => '500.00'],
            ],
        ]));
        $vente = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/paiements', [], [], $headers, json_encode([
            'facture_id' => $vente['facture']['id'],
            'amount' => '1000.00',
            'method' => 'cash',
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/factures/' . $vente['facture']['id'], [], [], $headers);
        $facture = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($facture['is_creance']);
        $this->assertNotNull($facture['credit_closed_at']);
    }

    public function testCreancesEndpointsFilterOpenAndClosed(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '30', '300.0000');
        $registeredClient = $this->createClientEntity($client, $headers);

        $client->request('POST', '/api/ventes', [], [], $headers, json_encode([
            'acheteur' => ['client_id' => $registeredClient['id']],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '2', 'unit_price' => '500.00'],
            ],
        ]));
        $openVente = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/ventes', [], [], $headers, json_encode([
            'acheteur' => ['client_id' => $registeredClient['id']],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '1', 'unit_price' => '500.00'],
            ],
        ]));
        $closedVente = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/paiements', [], [], $headers, json_encode([
            'facture_id' => $closedVente['facture']['id'],
            'amount' => '500.00',
            'method' => 'cash',
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/creances?status=open&client_id=' . $registeredClient['id'], [], [], $headers);
        $this->assertResponseIsSuccessful();
        $openItems = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $openItems);
        $this->assertSame($openVente['facture']['id'], $openItems[0]['id']);
        $this->assertSame('en_cours', $openItems[0]['statut']);

        $client->request('GET', '/api/creances?status=closed&client_id=' . $registeredClient['id'], [], [], $headers);
        $this->assertResponseIsSuccessful();
        $closedItems = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $closedItems);
        $this->assertSame($closedVente['facture']['id'], $closedItems[0]['id']);
        $this->assertSame('soldee', $closedItems[0]['statut']);
        $this->assertNotNull($closedItems[0]['credit_closed_at']);

        $client->request('GET', '/api/clients/' . $registeredClient['id'] . '/creances?status=all', [], [], $headers);
        $this->assertResponseIsSuccessful();
        $clientItems = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $clientItems);
    }

    public function testCancelledPaymentReopensCreance(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '20', '300.0000');
        $registeredClient = $this->createClientEntity($client, $headers);

        $client->request('POST', '/api/ventes', [], [], $headers, json_encode([
            'acheteur' => ['client_id' => $registeredClient['id']],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '2', 'unit_price' => '500.00'],
            ],
        ]));
        $vente = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/paiements', [], [], $headers, json_encode([
            'facture_id' => $vente['facture']['id'],
            'amount' => '1000.00',
            'method' => 'cash',
        ]));
        $paiement = json_decode($client->getResponse()->getContent(), true);

        $client->request('GET', '/api/factures/' . $vente['facture']['id'], [], [], $headers);
        $this->assertNotNull(json_decode($client->getResponse()->getContent(), true)['credit_closed_at']);

        $client->request('POST', '/api/paiements/' . $paiement['id'] . '/cancel', [], [], $headers);
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/api/factures/' . $vente['facture']['id'], [], [], $headers);
        $facture = json_decode($client->getResponse()->getContent(), true);
        $this->assertNull($facture['credit_closed_at']);

        $client->request('GET', '/api/creances?status=open&client_id=' . $registeredClient['id'], [], [], $headers);
        $openItems = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $openItems);
        $this->assertSame('en_cours', $openItems[0]['statut']);
    }

    public function testCancelledSaleIsExcludedFromOpenCreances(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '20', '300.0000');
        $registeredClient = $this->createClientEntity($client, $headers);

        $client->request('POST', '/api/ventes', [], [], $headers, json_encode([
            'acheteur' => ['client_id' => $registeredClient['id']],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '2', 'unit_price' => '500.00'],
            ],
        ]));
        $vente = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', "/api/ventes/{$vente['id']}/cancel", [], [], $headers);
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/api/creances?status=open&client_id=' . $registeredClient['id'], [], [], $headers);
        $openItems = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(0, $openItems);
    }

    public function testDevisCreationDoesNotImpactStock(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '100', '300.0000');

        $client->request('POST', '/api/devis', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Prospect devis'],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '5', 'unit_price' => '500.00'],
            ],
            'valid_until' => '2026-09-04',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $devis = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('actif', $devis['status']);
        $this->assertSame('2500.00', $devis['total_amount']);
        $this->assertSame('2026-09-04', $devis['valid_until']);

        $client->request('GET', "/api/variants/{$variantId}/stock", [], [], $headers);
        $this->assertEquals(100, (float) json_decode($client->getResponse()->getContent(), true)['available']);
    }

    public function testDevisConversionToVenteDecrementsStock(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '100', '300.0000');

        $client->request('POST', '/api/devis', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Client devis'],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '4', 'unit_price' => '500.00'],
            ],
        ]));
        $devis = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', "/api/devis/{$devis['id']}/convert/vente", [], [], $headers, json_encode([]));
        $this->assertResponseIsSuccessful();
        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('converti_vente', $result['devis']['status']);
        $this->assertNotNull($result['vente']['facture']);

        $client->request('GET', "/api/variants/{$variantId}/stock", [], [], $headers);
        $this->assertEquals(96, (float) json_decode($client->getResponse()->getContent(), true)['available']);
    }

    public function testDevisConversionToCommandeConfirmee(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '50', '300.0000');

        $client->request('POST', '/api/devis', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Client commande devis'],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '3', 'unit_price' => '400.00'],
            ],
        ]));
        $devis = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', "/api/devis/{$devis['id']}/convert/commande", [], [], $headers, json_encode([
            'confirm' => true,
            'delivery_date' => '2026-08-20',
        ]));
        $this->assertResponseIsSuccessful();
        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('converti_commande', $result['devis']['status']);
        $this->assertSame('confirmee', $result['commande']['status']);
    }

    public function testConvertedDevisCannotBeCancelled(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '20', '300.0000');

        $client->request('POST', '/api/devis', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Client'],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '1', 'unit_price' => '500.00'],
            ],
        ]));
        $devis = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', "/api/devis/{$devis['id']}/convert/vente", [], [], $headers, json_encode([]));
        $this->assertResponseIsSuccessful();

        $client->request('POST', "/api/devis/{$devis['id']}/cancel", [], [], $headers);
        $this->assertResponseStatusCodeSame(409);
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>
     */
    private function createClientEntity(KernelBrowser $client, array $headers): array
    {
        $client->request('POST', '/api/clients', [], [], $headers, json_encode([
            'name' => 'Client-' . uniqid(),
            'status' => 'active',
        ]));
        $this->assertResponseStatusCodeSame(201);

        return json_decode($client->getResponse()->getContent(), true);
    }

    public function testFreeLineSaleDoesNotDecrementStock(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '100', '300.0000');

        $client->request('POST', '/api/ventes', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Client libre'],
            'lines' => [
                ['label' => 'Prestation SAV', 'quantity' => '2', 'unit_price' => '1500.00'],
            ],
        ]));
        $this->assertResponseStatusCodeSame(201);
        $vente = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('3000.00', $vente['total_amount']);
        $this->assertSame('libre', $vente['lines'][0]['line_type']);
        $this->assertNull($vente['lines'][0]['variant_id']);

        $client->request('GET', "/api/variants/{$variantId}/stock", [], [], $headers);
        $this->assertEquals(100, (float) json_decode($client->getResponse()->getContent(), true)['available']);
    }

    public function testMixedSaleDecrementsStockOnlyForProductLine(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '100', '300.0000');

        $client->request('POST', '/api/ventes', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Client mixte'],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '3', 'unit_price' => '500.00'],
                ['label' => 'Frais de dossier', 'quantity' => '1', 'unit_price' => '200.00'],
            ],
        ]));
        $this->assertResponseStatusCodeSame(201);
        $vente = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('1700.00', $vente['total_amount']);
        $this->assertCount(2, $vente['lines']);

        $client->request('GET', "/api/variants/{$variantId}/stock", [], [], $headers);
        $this->assertEquals(97, (float) json_decode($client->getResponse()->getContent(), true)['available']);
    }

    public function testMixedOrderConfirmChecksStockOnlyForProductLines(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '10', '300.0000');

        $client->request('POST', '/api/commandes', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Client commande mixte'],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '2', 'unit_price' => '400.00'],
                ['label' => 'Installation', 'quantity' => '1', 'unit_price' => '1000.00'],
            ],
        ]));
        $this->assertResponseStatusCodeSame(201);
        $commande = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', "/api/commandes/{$commande['id']}/confirm", [], [], $headers, json_encode([
            'delivery_date' => '2026-08-20',
        ]));
        $this->assertResponseIsSuccessful();

        $client->request('GET', "/api/commandes/{$commande['id']}/reste-a-livrer", [], [], $headers);
        $reste = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $reste);
        $this->assertSame($variantId, $reste[0]['variant_id']);
    }

    public function testFreeDevisConversionToSalePreservesFreeLines(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);
        $variantId = $this->createVariantWithStock($client, $headers, '50', '300.0000');

        $client->request('POST', '/api/devis', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Prospect'],
            'valid_until' => '2026-09-01',
            'lines' => [
                ['label' => 'Consultation', 'quantity' => '1', 'unit_price' => '5000.00'],
            ],
        ]));
        $this->assertResponseStatusCodeSame(201);
        $devis = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', "/api/devis/{$devis['id']}/convert/vente", [], [], $headers, json_encode([]));
        $this->assertResponseIsSuccessful();
        $conversion = json_decode($client->getResponse()->getContent(), true);
        $vente = $conversion['vente'];
        $this->assertSame('5000.00', $vente['total_amount']);
        $this->assertSame('libre', $vente['lines'][0]['line_type']);

        $client->request('GET', "/api/variants/{$variantId}/stock", [], [], $headers);
        $this->assertEquals(50, (float) json_decode($client->getResponse()->getContent(), true)['available']);
    }

    public function testInvalidLineWithoutVariantOrLabelIsRejected(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();
        $headers = $this->authenticateAdmin($client);

        $client->request('POST', '/api/ventes', [], [], $headers, json_encode([
            'acheteur' => ['anonymous_info' => 'Client'],
            'lines' => [
                ['quantity' => '1', 'unit_price' => '100.00'],
            ],
        ]));
        $this->assertResponseStatusCodeSame(400);
    }

    /**
     * @param array<string, string> $headers
     */
    private function createVariantWithStock(KernelBrowser $client, array $headers, string $quantity, string $unitCost): string
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
