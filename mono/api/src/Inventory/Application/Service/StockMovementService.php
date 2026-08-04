<?php

namespace App\Inventory\Application\Service;

use App\Catalog\Domain\Entity\ProductVariant;
use App\IdentityAccess\Domain\Entity\User;
use App\Inventory\Domain\Entity\StockLot;
use App\Inventory\Domain\Entity\StockLotAllocation;
use App\Inventory\Domain\Entity\StockMovement;
use App\Inventory\Domain\Enum\MovementDirection;
use App\Inventory\Domain\Enum\MovementType;
use App\Inventory\Domain\Enum\StockPolicyStrategy;
use App\Inventory\Domain\Exception\InsufficientStockException;
use App\Inventory\Domain\Repository\StockLotAllocationRepositoryInterface;
use App\Inventory\Domain\Repository\StockLotRepositoryInterface;
use App\Inventory\Domain\Repository\StockMovementRepositoryInterface;
use App\Inventory\Domain\Repository\StockPolicyRepositoryInterface;
use App\Inventory\Domain\Service\StockAllocationService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

final class StockMovementService
{
    public function __construct(
        private readonly StockLotRepositoryInterface $stockLotRepository,
        private readonly StockMovementRepositoryInterface $stockMovementRepository,
        private readonly StockPolicyRepositoryInterface $stockPolicyRepository,
        private readonly StockLotAllocationRepositoryInterface $allocationRepository,
        private readonly StockAllocationService $allocationService,
        private readonly Security $security,
    ) {
    }

    public function receiveLot(
        ProductVariant $variant,
        string $quantity,
        string $unitCost,
        ?string $reference = null,
        ?string $supplierRef = null,
        ?\DateTimeImmutable $expiryDate = null,
        ?Uuid $fournisseurId = null,
    ): StockLot {
        $lot = new StockLot(
            $variant,
            $quantity,
            $unitCost,
            $reference,
            $supplierRef,
            $expiryDate,
            $fournisseurId,
        );

        $movement = new StockMovement(
            $variant,
            MovementType::Purchase,
            MovementDirection::In,
            $quantity,
            $unitCost,
            'Lot reception',
            $this->currentUser(),
        );

        $this->stockLotRepository->save($lot, false);
        $this->stockMovementRepository->save($movement);

        return $lot;
    }

    /**
     * @param list<array{lot_id: string, quantity: string}>|null $manualAllocations
     */
    public function stockOut(
        ProductVariant $variant,
        string $quantity,
        MovementType $type = MovementType::Adjustment,
        ?string $reason = null,
        ?array $manualAllocations = null,
        ?string $sourceRef = null,
    ): StockMovement {
        $available = $this->stockLotRepository->sumAvailableStock($variant);
        if (bccomp($available, $quantity, 3) < 0) {
            throw new InsufficientStockException(sprintf('Insufficient stock: available %s, requested %s', $available, $quantity));
        }

        $policy = $this->stockPolicyRepository->findByVariant($variant);
        $strategy = $policy?->getStrategy() ?? StockPolicyStrategy::Fifo;

        $movement = new StockMovement(
            $variant,
            $type,
            MovementDirection::Out,
            $quantity,
            null,
            $reason,
            $this->currentUser(),
        );
        $movement->setSourceRef($sourceRef);

        if (null !== $manualAllocations && [] !== $manualAllocations) {
            $this->allocateManual($movement, $manualAllocations, $quantity);
        } elseif (StockPolicyStrategy::Manual === $strategy) {
            throw new \InvalidArgumentException('Manual policy requires explicit allocations.');
        } else {
            $this->allocateAutomatic($movement, $variant, $quantity, $strategy);
        }

        $this->stockMovementRepository->save($movement);

        return $movement;
    }

    public function adjust(
        ProductVariant $variant,
        string $quantity,
        MovementDirection $direction,
        string $lotId,
        ?string $reason = null,
    ): StockMovement {
        $lot = $this->stockLotRepository->findById(Uuid::fromString($lotId));
        if (!$lot instanceof StockLot) {
            throw new \InvalidArgumentException('Invalid lot_id: ' . $lotId);
        }

        if ((string) $lot->getVariant()->getId() !== (string) $variant->getId()) {
            throw new \InvalidArgumentException('Lot does not belong to this variant.');
        }

        if (MovementDirection::In === $direction) {
            return $this->adjustLotIn($variant, $lot, $quantity, $reason);
        }

        if (bccomp($lot->getQuantityRemaining(), $quantity, 3) < 0) {
            throw new InsufficientStockException(sprintf(
                'Insufficient lot stock: available %s, requested %s',
                $lot->getQuantityRemaining(),
                $quantity,
            ));
        }

        return $this->stockOut(
            $variant,
            $quantity,
            MovementType::Adjustment,
            $reason,
            [['lot_id' => $lotId, 'quantity' => $quantity]],
        );
    }

    public function getAvailableStock(ProductVariant $variant): string
    {
        return $this->stockLotRepository->sumAvailableStock($variant);
    }

    /**
     * Compensating restock used when a previously decremented operation is
     * reversed (e.g. an order cancellation). The quantity is returned to the
     * most recent lot, or a new zero-cost lot if none remains.
     */
    public function restock(
        ProductVariant $variant,
        string $quantity,
        ?string $reason = null,
        ?string $sourceRef = null,
    ): StockMovement {
        $lots = $this->stockLotRepository->findByVariantOrderedByReceivedAt($variant);
        $lot = $lots[0] ?? null;

        if (null === $lot) {
            $lot = new StockLot($variant, '0', '0.0000', 'Restock');
            $this->stockLotRepository->save($lot, false);
        }

        $lot->increase($quantity);

        $movement = new StockMovement(
            $variant,
            MovementType::Adjustment,
            MovementDirection::In,
            $quantity,
            $lot->getUnitCost(),
            $reason ?? 'Compensating restock',
            $this->currentUser(),
        );
        $movement->setSourceRef($sourceRef);

        $this->stockLotRepository->save($lot, false);
        $this->stockMovementRepository->save($movement);

        return $movement;
    }

    private function adjustLotIn(
        ProductVariant $variant,
        StockLot $lot,
        string $quantity,
        ?string $reason,
    ): StockMovement {
        $lot->increase($quantity);

        $movement = new StockMovement(
            $variant,
            MovementType::Adjustment,
            MovementDirection::In,
            $quantity,
            $lot->getUnitCost(),
            $reason ?? 'Stock adjustment IN',
            $this->currentUser(),
        );

        $this->stockLotRepository->save($lot, false);
        $this->stockMovementRepository->save($movement);

        return $movement;
    }

    private function allocateAutomatic(
        StockMovement $movement,
        ProductVariant $variant,
        string $quantity,
        StockPolicyStrategy $strategy,
    ): void {
        $lots = $this->stockLotRepository->findAvailableByVariant($variant, $strategy);
        $allocations = $this->allocationService->allocateFromLots($lots, $quantity, $movement);

        foreach ($allocations as $allocation) {
            $this->allocationRepository->save($allocation, false);
        }
    }

    /**
     * @param list<array{lot_id: string, quantity: string}> $manualAllocations
     */
    private function allocateManual(StockMovement $movement, array $manualAllocations, string $expectedTotal): void
    {
        $total = '0';
        foreach ($manualAllocations as $item) {
            $lot = $this->stockLotRepository->findById(Uuid::fromString($item['lot_id']));
            if (!$lot instanceof StockLot) {
                throw new \InvalidArgumentException('Invalid lot_id: ' . $item['lot_id']);
            }

            $qty = (string) $item['quantity'];
            $allocation = new StockLotAllocation($movement, $lot, $qty, $lot->getUnitCost());
            $lot->consume($qty);
            $this->allocationRepository->save($allocation, false);
            $total = bcadd($total, $qty, 3);
        }

        if (bccomp($total, $expectedTotal, 3) !== 0) {
            throw new \InvalidArgumentException('Manual allocations do not match requested quantity.');
        }
    }

    private function currentUser(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user : null;
    }
}
