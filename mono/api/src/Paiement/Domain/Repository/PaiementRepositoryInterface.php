<?php

namespace App\Paiement\Domain\Repository;

use App\Paiement\Domain\Entity\Paiement;
use Symfony\Component\Uid\Uuid;

interface PaiementRepositoryInterface
{
    public function save(Paiement $paiement, bool $flush = true): void;

    public function findById(Uuid $id): ?Paiement;

    /** @return list<Paiement> */
    public function findAll(): array;

    /** @return list<Paiement> */
    public function findByFactureId(Uuid $factureId): array;

    /** @return list<Paiement> */
    public function findByCommandeId(Uuid $commandeId): array;

    /** @return list<Paiement> */
    public function findByClientId(Uuid $clientId): array;
}
