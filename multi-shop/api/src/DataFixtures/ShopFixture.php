<?php

namespace App\DataFixtures;

use App\Shop\Domain\Entity\Shop;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class ShopFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $shop = new Shop('Boutique Demo', 'demo');
        $shop->setCurrency('XOF');
        $shop->setAddress('Bamako, Mali');
        $shop->setPhone('+223 XX XX XX XX');

        $manager->persist($shop);
        $manager->flush();

        $this->addReference(FixtureReferences::DEFAULT_SHOP, $shop);
    }
}
