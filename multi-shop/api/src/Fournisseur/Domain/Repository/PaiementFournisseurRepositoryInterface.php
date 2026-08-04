<?php

namespace App\Fournisseur\Domain\Repository;

use App\Fournisseur\Domain\Entity\PaiementFournisseur;
use Symfony\Component\Uid\Uuid;

interface PaiementFournisseurRepositoryInterface
{
    public function save(PaiementFournisseur $paiement, bool $flush = true): void;

    public function findById(Uuid $id): ?PaiementFournisseur;

    /** @return list<PaiementFournisseur> */
    public function findAll(): array;

    /** @return list<PaiementFournisseur> */
    public function findByDetteId(Uuid $detteId): array;

    /** @return list<PaiementFournisseur> */
    public function findByFournisseurId(Uuid $fournisseurId): array;
}
