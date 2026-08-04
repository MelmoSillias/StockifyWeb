<?php

namespace App\Finance\Domain\Repository;

use App\Finance\Domain\Entity\Compte;
use Symfony\Component\Uid\Uuid;

interface CompteRepositoryInterface
{
    public function findAll(): array;

    public function findById(Uuid $id): ?Compte;

    public function findDefault(): ?Compte;

    public function countAll(): int;

    public function save(Compte $compte, bool $flush = true): void;
}
