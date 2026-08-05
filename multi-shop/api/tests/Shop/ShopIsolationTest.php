<?php

namespace App\Tests\Shop;

use App\IdentityAccess\Domain\Entity\User;
use App\Shop\Domain\Entity\Shop;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class ShopIsolationTest extends ApiTestCase
{
    public function testOwnerRequiresShopHeaderForBusinessEndpoints(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();
        $this->createShopAndPromoteAdmin();
        $headers = $this->authenticateAdmin($client);

        $client->request('GET', '/api/products', [], [], $headers);

        self::assertResponseStatusCodeSame(400);
    }

    public function testOwnerCanAccessShopScopedProductsWithHeader(): void
    {
        $this->initializeTestSchema();
        $client = static::createClient();
        $shopId = $this->createShopAndPromoteAdmin();
        $headers = $this->authenticateAdmin($client);
        $headers['HTTP_X-Shop-Id'] = $shopId;

        $client->request('GET', '/api/products', [], [], $headers);

        self::assertResponseIsSuccessful();
    }

    private function createShopAndPromoteAdmin(): string
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $shop = $em->getRepository(Shop::class)->findOneBy(['slug' => 'isolation-shop']);
        if (null === $shop) {
            $shop = new Shop('Isolation Shop', 'isolation-shop');
            $em->persist($shop);
        }

        $admin = $em->getRepository(User::class)->findOneBy(['email' => 'admin-test@stockify.local']);
        self::assertInstanceOf(User::class, $admin);
        if (!$admin->isPlatformOwner()) {
            $admin->promoteToPlatformOwner();
        }

        $em->flush();

        return (string) $shop->getId();
    }
}
