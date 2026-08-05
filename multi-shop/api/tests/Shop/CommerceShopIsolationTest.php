<?php

namespace App\Tests\Shop;

use App\IdentityAccess\Domain\Entity\User;
use App\Shop\Domain\Entity\Shop;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Commerce and Facturation aggregates used to escape the shop_scope filter,
 * which leaked sales and invoices across shops of the same deployment.
 */
final class CommerceShopIsolationTest extends ApiTestCase
{
    public function testSalesAndInvoicesAreNotVisibleFromAnotherShop(): void
    {
        $this->initializeTestSchema(true);
        $client = static::createClient();

        [$shopA, $shopB] = $this->createShopsAndPromoteAdmin();

        $headersA = $this->authenticateAdmin($client);
        $headersA['HTTP_X-Shop-Id'] = $shopA;

        $variantId = $this->createVariantWithStock($client, $headersA);

        $client->request('POST', '/api/ventes', [], [], $headersA, json_encode([
            'acheteur' => ['anonymous_info' => 'Client boutique A'],
            'lines' => [
                ['variant_id' => $variantId, 'quantity' => '2', 'unit_price' => '500.00'],
            ],
        ]));
        $this->assertResponseStatusCodeSame(201);
        $vente = json_decode($client->getResponse()->getContent(), true);

        $client->request('GET', '/api/ventes', [], [], $headersA);
        $this->assertResponseIsSuccessful();
        $ownShopSales = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEmpty(
            array_filter($ownShopSales, static fn (array $v): bool => $v['id'] === $vente['id']),
            'The sale must be visible from the shop that created it.',
        );

        $headersB = $this->authenticateAdmin($client);
        $headersB['HTTP_X-Shop-Id'] = $shopB;

        $client->request('GET', '/api/ventes', [], [], $headersB);
        $this->assertResponseIsSuccessful();
        $otherShopSales = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(
            [],
            array_values(array_filter($otherShopSales, static fn (array $v): bool => $v['id'] === $vente['id'])),
            'A sale must never leak into another shop.',
        );

        $client->request('GET', '/api/factures', [], [], $headersB);
        $this->assertResponseIsSuccessful();
        $otherShopInvoices = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(
            [],
            array_values(array_filter($otherShopInvoices, static fn (array $f): bool => $f['vente_id'] === $vente['id'])),
            'An invoice must never leak into another shop.',
        );
    }

    /** @return array{0: string, 1: string} */
    private function createShopsAndPromoteAdmin(): array
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $shopA = new Shop('Boutique A', 'boutique-a');
        $shopB = new Shop('Boutique B', 'boutique-b');
        $em->persist($shopA);
        $em->persist($shopB);

        $admin = $em->getRepository(User::class)->findOneBy(['email' => 'admin-test@stockify.local']);
        $this->assertInstanceOf(User::class, $admin);
        $admin->promoteToPlatformOwner();

        $em->flush();

        return [(string) $shopA->getId(), (string) $shopB->getId()];
    }

    private function createVariantWithStock(KernelBrowser $client, array $headers): string
    {
        $client->request('GET', '/api/units-of-measure', [], [], $headers);
        $unitId = json_decode($client->getResponse()->getContent(), true)[0]['id'];

        $client->request('POST', '/api/categories', [], [], $headers, json_encode(['name' => 'Cat-'.uniqid()]));
        $this->assertResponseStatusCodeSame(201, $client->getResponse()->getContent());
        $category = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/products', [], [], $headers, json_encode([
            'name' => 'Produit-'.uniqid(),
            'category_id' => $category['id'],
        ]));
        $this->assertResponseStatusCodeSame(201, $client->getResponse()->getContent());
        $product = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', "/api/products/{$product['id']}/variants", [], [], $headers, json_encode([
            'sku' => 'SKU-'.uniqid(),
            'unit_of_measure_id' => $unitId,
            'sale_mode' => 'unit',
            'default_price' => '500.00',
        ]));
        $this->assertResponseStatusCodeSame(201, $client->getResponse()->getContent());
        $variant = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', "/api/variants/{$variant['id']}/lots", [], [], $headers, json_encode([
            'quantity' => '100',
            'unit_cost' => '300.0000',
        ]));
        $this->assertResponseStatusCodeSame(201);

        return $variant['id'];
    }
}
