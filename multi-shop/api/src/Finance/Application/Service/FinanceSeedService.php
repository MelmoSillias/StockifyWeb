<?php

namespace App\Finance\Application\Service;

use App\Finance\Domain\Entity\Compte;
use App\Finance\Domain\Entity\ModeDePaiement;
use App\Finance\Domain\Enum\CompteType;
use App\Finance\Domain\Repository\CompteRepositoryInterface;
use App\Finance\Domain\Repository\ModeDePaiementRepositoryInterface;

final class FinanceSeedService
{
    public function __construct(
        private readonly CompteRepositoryInterface $compteRepository,
        private readonly ModeDePaiementRepositoryInterface $modeDePaiementRepository,
    ) {
    }

    public function seedIfEmpty(): void
    {
        if ($this->compteRepository->countAll() > 0) {
            return;
        }

        $caisse = new Compte('Caisse', CompteType::Caisse, true);
        $banque = new Compte('Compte bancaire', CompteType::Banque, false);

        $this->compteRepository->save($caisse, false);
        $this->compteRepository->save($banque, false);

        $modes = [
            ['cash', 'Espèces', $caisse->getId(), true],
            ['mobile_money', 'Mobile Money', $caisse->getId(), true],
            ['card', 'Carte', $banque->getId(), true],
            ['transfer', 'Virement', $banque->getId(), true],
            ['credit', 'Crédit', $caisse->getId(), false],
        ];

        foreach ($modes as [$code, $label, $compteId, $generatesTransaction]) {
            $mode = new ModeDePaiement($code, $label, $compteId, $generatesTransaction);
            $this->modeDePaiementRepository->save($mode, false);
        }

        $this->compteRepository->save($caisse);
    }
}
