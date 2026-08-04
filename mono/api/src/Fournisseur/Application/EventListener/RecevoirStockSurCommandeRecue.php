<?php

namespace App\Fournisseur\Application\EventListener;

use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use App\Fournisseur\Domain\Event\CommandeFournisseurRecue;
use App\Fournisseur\Domain\Repository\CommandeFournisseurRepositoryInterface;
use App\Inventory\Application\Service\StockMovementService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: CommandeFournisseurRecue::class, priority: 10)]
final class RecevoirStockSurCommandeRecue
{
    public function __construct(
        private readonly CommandeFournisseurRepositoryInterface $commandeRepository,
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly StockMovementService $stockMovementService,
    ) {
    }

    public function __invoke(CommandeFournisseurRecue $event): void
    {
        $commande = $this->commandeRepository->findById($event->commandeFournisseurId());
        if (null === $commande) {
            return;
        }

        foreach ($commande->getLines() as $line) {
            $variant = $this->variantRepository->findById($line->getVariantId());
            if (null === $variant) {
                continue;
            }

            $this->stockMovementService->receiveLot(
                $variant,
                $line->getQuantity(),
                $line->getUnitCost(),
                $commande->getReference(),
                null,
                null,
                $event->fournisseurId(),
            );
        }
    }
}
