<?php

namespace App\Commerce\Domain\Repository;

use App\Commerce\Domain\Entity\Vente;
use Symfony\Component\Uid\Uuid;

interface VenteRepositoryInterface
{
    public function save(Vente $vente, bool $flush = true): void;

    public function findById(Uuid $id): ?Vente;

    /** @return list<Vente> */
    public function findAll(): array;

    /** @return list<Vente> */
    public function findByClientId(Uuid $clientId): array;

    public function existsByClientId(Uuid $clientId): bool;
}
