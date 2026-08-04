<?php

namespace App\DataFixtures;

use App\Finance\Application\Service\FinanceSeedService;
use App\SharedKernel\Domain\ValueObject\ShopContext;
use App\SharedKernel\Infrastructure\Shop\ShopContextHolder;
use App\Shop\Domain\Entity\Shop;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class FinanceFixture extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly FinanceSeedService $financeSeedService,
        private readonly ShopContextHolder $shopContextHolder,
    ) {
    }

    public function getDependencies(): array
    {
        return [ShopFixture::class, UserFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        $shop = $this->getReference(FixtureReferences::DEFAULT_SHOP, Shop::class);
        $this->shopContextHolder->set(new ShopContext($shop->getId(), $shop->getName(), $shop->getSlug()));
        $this->financeSeedService->seedIfEmpty();
    }
}
