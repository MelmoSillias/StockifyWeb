<?php

namespace App\Facturation\Domain\Repository;

use App\Facturation\Domain\Entity\Facture;
use App\Facturation\Domain\Enum\CreanceFilterStatus;
use Symfony\Component\Uid\Uuid;

interface FactureRepositoryInterface
{
    public function save(Facture $facture, bool $flush = true): void;

    public function findById(Uuid $id): ?Facture;

    /** @return list<Facture> */
    public function findAll(): array;

    public function findByVenteId(Uuid $venteId): ?Facture;

    public function findByCommandeId(Uuid $commandeId): ?Facture;

    /** @return list<Facture> */
    public function findByClientId(Uuid $clientId): array;

    /** @return list<Facture> */
    public function findCreances(?Uuid $clientId, CreanceFilterStatus $status): array;

    public function existsByClientId(Uuid $clientId): bool;
}
