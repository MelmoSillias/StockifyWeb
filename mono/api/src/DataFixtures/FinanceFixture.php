<?php

namespace App\DataFixtures;

use App\Finance\Application\Service\FinanceSeedService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class FinanceFixture extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly FinanceSeedService $financeSeedService,
    ) {
    }

    public function getDependencies(): array
    {
        return [UserFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        $this->financeSeedService->seedIfEmpty();
    }
}
