<?php

namespace App\Fournisseur\Domain\Repository;

use App\Fournisseur\Domain\Entity\Fournisseur;
use Symfony\Component\Uid\Uuid;

interface FournisseurRepositoryInterface
{
    public function save(Fournisseur $fournisseur, bool $flush = true): void;

    public function remove(Fournisseur $fournisseur, bool $flush = true): void;

    public function findById(Uuid $id): ?Fournisseur;

    /** @return list<Fournisseur> */
    public function findAll(): array;
}
