<?php

namespace App\Inventory\Application\Service;

use App\Catalog\Domain\Entity\ProductVariant;
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
use App\SharedKernel\Domain\ValueObject\TenantContext;
use Symfony\Component\Uid\Uuid;

final class StockMovementService
{
    public function __construct(
        private readonly StockLotRepositoryInterface $stockLotRepository,
        private readonly StockMovementRepositoryInterface $stockMovementRepository,
        private readonly StockPolicyRepositoryInterface $stockPolicyRepository,
        private readonly StockLotAllocationRepositoryInterface $allocationRepository,
        private readonly StockAllocationService $allocationService,
    ) {
    }

    public function receiveLot(
        TenantContext $context,
        ProductVariant $variant,
        string $quantity,
        string $unitCost,
        ?string $reference = null,
        ?string $supplierRef = null,
        ?\DateTimeImmutable $expiryDate = null,
    ): StockLot {
        $accountId = $context->getAccount()->getId();
        $shopId = $context->getShop()->getId();

        $lot = new StockLot(
            $accountId,
            $shopId,
            $variant,
            $quantity,
            $unitCost,
            $reference,
            $supplierRef,
            $expiryDate,
        );

        $movement = new StockMovement(
            $accountId,
            $shopId,
            $variant,
            MovementType::Purchase,
            MovementDirection::In,
            $quantity,
            $unitCost,
            'Lot reception',
            $context->getUser(),
        );

        $this->stockLotRepository->save($lot, false);
        $this->stockMovementRepository->save($movement);

        return $lot;
    }

    /**
     * @param list<array{lot_id: string, quantity: string}>|null $manualAllocations
     */
    public function stockOut(
        TenantContext $context,
        ProductVariant $variant,
        string $quantity,
        MovementType $type = MovementType::Adjustment,
        ?string $reason = null,
        ?array $manualAllocations = null,
    ): StockMovement {
        $available = $this->stockLotRepository->sumAvailableStock($variant);
        if (bccomp($available, $quantity, 3) < 0) {
            throw new InsufficientStockException(sprintf('Insufficient stock: available %s, requested %s', $available, $quantity));
        }

        $policy = $this->stockPolicyRepository->findByVariant($variant);
        $strategy = $policy?->getStrategy() ?? StockPolicyStrategy::Fifo;

        $accountId = $context->getAccount()->getId();
        $shopId = $context->getShop()->getId();

        $movement = new StockMovement(
            $accountId,
            $shopId,
            $variant,
            $type,
            MovementDirection::Out,
            $quantity,
            null,
            $reason,
            $context->getUser(),
        );

        if (StockPolicyStrategy::Manual === $strategy) {
            if (null === $manualAllocations || [] === $manualAllocations) {
                throw new \InvalidArgumentException('Manual policy requires explicit allocations.');
            }
            $this->allocateManual($movement, $manualAllocations, $quantity);
        } else {
            $this->allocateAutomatic($movement, $variant, $quantity, $strategy);
        }

        $this->stockMovementRepository->save($movement);

        return $movement;
    }

    public function adjust(
        TenantContext $context,
        ProductVariant $variant,
        string $quantity,
        MovementDirection $direction,
        ?string $reason = null,
    ): StockMovement {
        if (MovementDirection::In === $direction) {
            return $this->receiveAdjustmentLot($context, $variant, $quantity, $reason);
        }

        return $this->stockOut($context, $variant, $quantity, MovementType::Adjustment, $reason);
    }

    public function getAvailableStock(ProductVariant $variant): string
    {
        return $this->stockLotRepository->sumAvailableStock($variant);
    }

    private function receiveAdjustmentLot(
        TenantContext $context,
        ProductVariant $variant,
        string $quantity,
        ?string $reason,
    ): StockMovement {
        $accountId = $context->getAccount()->getId();
        $shopId = $context->getShop()->getId();

        $lot = new StockLot(
            $accountId,
            $shopId,
            $variant,
            $quantity,
            '0.0000',
            'ADJ-' . uniqid(),
        );

        $movement = new StockMovement(
            $accountId,
            $shopId,
            $variant,
            MovementType::Adjustment,
            MovementDirection::In,
            $quantity,
            '0.0000',
            $reason ?? 'Stock adjustment IN',
            $context->getUser(),
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
}
