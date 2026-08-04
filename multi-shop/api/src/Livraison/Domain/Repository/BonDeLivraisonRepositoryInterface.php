<?php

namespace App\Livraison\Domain\Repository;

use App\Livraison\Domain\Entity\BonDeLivraison;
use Symfony\Component\Uid\Uuid;

interface BonDeLivraisonRepositoryInterface
{
    public function save(BonDeLivraison $bonDeLivraison, bool $flush = true): void;

    public function findById(Uuid $id): ?BonDeLivraison;

    /** @return list<BonDeLivraison> */
    public function findByCommandeId(Uuid $commandeId): array;

    /**
     * @return array<string, string> variantId => total shipped quantity
     */
    public function sumShippedQuantitiesByCommandeId(Uuid $commandeId): array;
}
