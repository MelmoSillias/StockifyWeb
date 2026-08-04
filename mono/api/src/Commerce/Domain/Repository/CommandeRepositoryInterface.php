<?php

namespace App\Commerce\Domain\Repository;

use App\Commerce\Domain\Entity\Commande;
use Symfony\Component\Uid\Uuid;

interface CommandeRepositoryInterface
{
    public function save(Commande $commande, bool $flush = true): void;

    public function findById(Uuid $id): ?Commande;

    /** @return list<Commande> */
    public function findAll(): array;

    /** @return list<Commande> */
    public function findByClientId(Uuid $clientId): array;

    /** @return list<Commande> */
    public function findActiveForStockReservation(): array;

    public function existsByClientId(Uuid $clientId): bool;
}
