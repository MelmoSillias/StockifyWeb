<?php

namespace App\Fournisseur\Application\Service;

use App\Fournisseur\Domain\Entity\Fournisseur;
use App\Fournisseur\Domain\Repository\CommandeFournisseurRepositoryInterface;
use App\Fournisseur\Domain\Repository\DetteFournisseurRepositoryInterface;
use App\Fournisseur\Domain\Repository\FournisseurRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class FournisseurDeletionService
{
    public function __construct(
        private readonly FournisseurRepositoryInterface $fournisseurRepository,
        private readonly CommandeFournisseurRepositoryInterface $commandeFournisseurRepository,
        private readonly DetteFournisseurRepositoryInterface $detteRepository,
    ) {
    }

    /** @return 'hard'|'soft' */
    public function delete(Fournisseur $fournisseur): string
    {
        $fournisseurId = $fournisseur->getId();

        if ($this->hasLinkedRecords($fournisseurId)) {
            $fournisseur->markDeleted();
            $this->fournisseurRepository->save($fournisseur);

            return 'soft';
        }

        $this->fournisseurRepository->remove($fournisseur);

        return 'hard';
    }

    private function hasLinkedRecords(Uuid $fournisseurId): bool
    {
        return $this->commandeFournisseurRepository->existsByFournisseurId($fournisseurId)
            || $this->detteRepository->existsByFournisseurId($fournisseurId);
    }
}
