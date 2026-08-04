<?php

namespace App\Fournisseur\Domain\Repository;

use App\Fournisseur\Domain\Entity\DetteFournisseur;
use App\Fournisseur\Domain\Enum\DetteFilterStatus;
use Symfony\Component\Uid\Uuid;

interface DetteFournisseurRepositoryInterface
{
    public function save(DetteFournisseur $dette, bool $flush = true): void;

    public function findById(Uuid $id): ?DetteFournisseur;

    /** @return list<DetteFournisseur> */
    public function findDettes(?Uuid $fournisseurId, DetteFilterStatus $status): array;

    public function existsByFournisseurId(Uuid $fournisseurId): bool;
}
