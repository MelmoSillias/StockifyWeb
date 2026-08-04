<?php

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Entity\UnitOfMeasure;
use Symfony\Component\Uid\Uuid;

interface UnitOfMeasureRepositoryInterface
{
    public function save(UnitOfMeasure $unit, bool $flush = true): void;

    public function findById(Uuid $id): ?UnitOfMeasure;

    public function findByCode(string $code): ?UnitOfMeasure;

    /** @return list<UnitOfMeasure> */
    public function findAllOrdered(): array;
}
