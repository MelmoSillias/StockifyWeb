<?php

namespace App\Fournisseur\Domain\Repository;

use App\Fournisseur\Domain\Entity\CommandeFournisseur;
use Symfony\Component\Uid\Uuid;

interface CommandeFournisseurRepositoryInterface
{
    public function save(CommandeFournisseur $commande, bool $flush = true): void;

    public function findById(Uuid $id): ?CommandeFournisseur;

    /** @return list<CommandeFournisseur> */
    public function findAll(): array;

    /** @return list<CommandeFournisseur> */
    public function findByFournisseurId(Uuid $fournisseurId): array;

    public function existsByFournisseurId(Uuid $fournisseurId): bool;
}
