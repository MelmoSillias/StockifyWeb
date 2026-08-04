<?php

namespace App\Facturation\Domain\Repository;

use App\Facturation\Domain\Entity\Avoir;
use Symfony\Component\Uid\Uuid;

interface AvoirRepositoryInterface
{
    public function save(Avoir $avoir, bool $flush = true): void;

    public function findById(Uuid $id): ?Avoir;

    public function findByVenteId(Uuid $venteId): ?Avoir;
}
