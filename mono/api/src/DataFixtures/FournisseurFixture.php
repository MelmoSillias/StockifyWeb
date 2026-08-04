<?php

namespace App\DataFixtures;

use App\Fournisseur\Application\Service\AchatsService;
use App\Fournisseur\Domain\Entity\Fournisseur;
use App\Fournisseur\Domain\Repository\FournisseurRepositoryInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class FournisseurFixture extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly FournisseurRepositoryInterface $fournisseurRepository,
        private readonly AchatsService $achatsService,
    ) {
    }

    public function getDependencies(): array
    {
        return [CatalogFixture::class, FinanceFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
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
