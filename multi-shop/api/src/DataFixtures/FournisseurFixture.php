<?php

namespace App\DataFixtures;

use App\Fournisseur\Application\Service\AchatsService;
use App\Fournisseur\Domain\Entity\Fournisseur;
use App\Fournisseur\Domain\Repository\FournisseurRepositoryInterface;
use App\SharedKernel\Domain\ValueObject\ShopContext;
use App\SharedKernel\Infrastructure\Shop\ShopContextHolder;
use App\Shop\Domain\Entity\Shop;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class FournisseurFixture extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly FournisseurRepositoryInterface $fournisseurRepository,
        private readonly AchatsService $achatsService,
        private readonly ShopContextHolder $shopContextHolder,
    ) {
    }

    public function getDependencies(): array
    {
        return [ShopFixture::class, CatalogFixture::class, FinanceFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        $shop = $this->getReference(FixtureReferences::DEFAULT_SHOP, Shop::class);
        $this->shopContextHolder->set(new ShopContext($shop->getId(), $shop->getName(), $shop->getSlug()));

        $grossiste = new Fournisseur('Grossiste ABC');
        $grossiste->setPhone('01 23 45 67 89');
        $grossiste->setEmail('contact@grossiste-abc.local');

        $local = new Fournisseur('Fournisseur Local');
        $local->setPhone('06 98 76 54 32');

        $this->fournisseurRepository->save($grossiste);
        $this->fournisseurRepository->save($local);

        $this->achatsService->creerDetteManuelle([
            'fournisseur_id' => (string) $local->getId(),
            'total_amount' => '250.00',
            'label' => 'Dette démo fournisseur',
        ]);

        $manager->flush();
    }
}
