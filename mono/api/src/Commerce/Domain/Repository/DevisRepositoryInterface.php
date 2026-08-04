<?php

namespace App\Commerce\Domain\Repository;

use App\Commerce\Domain\Entity\Devis;
use Symfony\Component\Uid\Uuid;

interface DevisRepositoryInterface
{
    public function save(Devis $devis, bool $flush = true): void;

    public function findById(Uuid $id): ?Devis;

    /** @return list<Devis> */
    public function findAll(): array;
}
