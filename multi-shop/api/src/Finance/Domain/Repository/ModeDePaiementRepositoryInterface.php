<?php

namespace App\Finance\Domain\Repository;

use App\Finance\Domain\Entity\ModeDePaiement;
use Symfony\Component\Uid\Uuid;

interface ModeDePaiementRepositoryInterface
{
    public function findAll(): array;

    public function findActive(): array;

    public function findById(Uuid $id): ?ModeDePaiement;

    public function findByCode(string $code): ?ModeDePaiement;

    public function countAll(): int;

    public function save(ModeDePaiement $mode, bool $flush = true): void;

    public function remove(ModeDePaiement $mode, bool $flush = true): void;
}
