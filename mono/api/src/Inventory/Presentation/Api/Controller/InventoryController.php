<?php

namespace App\Inventory\Presentation\Api\Controller;

use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use App\Inventory\Application\Query\GetStockAlerts\GetStockAlertsHandler;
use App\Inventory\Application\Query\GetStockAlerts\GetStockAlertsQuery;
use App\Inventory\Application\Query\ListStockMovements\ListStockMovementsHandler;
use App\Inventory\Application\Query\ListStockMovements\ListStockMovementsQuery;
use App\Inventory\Application\Service\StockMovementService;
use App\Inventory\Domain\Enum\MovementDirection;
use App\Inventory\Domain\Enum\MovementType;
use App\Inventory\Domain\Enum\StockPolicyStrategy;
use App\Inventory\Domain\Exception\InsufficientStockException;
use App\Inventory\Domain\Repository\StockLotRepositoryInterface;
use App\Inventory\Domain\Repository\StockPolicyRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class InventoryController extends AbstractController
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly StockPolicyRepositoryInterface $stockPolicyRepository,
        private readonly StockLotRepositoryInterface $stockLotRepository,
        private readonly StockMovementService $stockMovementService,
        private readonly GetStockAlertsHandler $stockAlertsHandler,
        private readonly ListStockMovementsHandler $listStockMovementsHandler,
    ) {
    }

    #[Route('/variants/{id}/stock-policy', name: 'api_stock_policy_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function showPolicy(string $id): JsonResponse
    {
        $variant = $this->getVariant($id);
        $policy = $this->stockPolicyRepository->findByVariant($variant);

        return $this->json([
            'variant_id' => (string) $variant->getId(),
            'strategy' => $policy?->getStrategy()->value ?? StockPolicyStrategy::Fifo->value,
        ]);
    }

    #[Route('/variants/{id}/stock-policy', name: 'api_stock_policy_update', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updatePolicy(string $id, Request $request): JsonResponse
    {
        $variant = $this->getVariant($id);
        $policy = $this->stockPolicyRepository->findByVariant($variant);
        if (null === $policy) {
            throw $this->createNotFoundException('Stock policy not found');
        }

        $data = $request->toArray();
        if (empty($data['strategy'])) {
            return $this->json(['error' => 'strategy is required'], Response::HTTP_BAD_REQUEST);
        }

        $policy->setStrategy(StockPolicyStrategy::from($data['strategy']));
        $this->stockPolicyRepository->save($policy);

        return $this->json(['strategy' => $policy->getStrategy()->value]);
    }

    #[Route('/variants/{id}/lots', name: 'api_lots_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function receiveLot(string $id, Request $request): JsonResponse
    {
        $variant = $this->getVariant($id);
        $data = $request->toArray();
        if (empty($data['quantity']) || empty($data['unit_cost'])) {
            return $this->json(['error' => 'quantity and unit_cost are required'], Response::HTTP_BAD_REQUEST);
        }

        $expiry = !empty($data['expiry_date']) ? new \DateTimeImmutable($data['expiry_date']) : null;
        $lot = $this->stockMovementService->receiveLot(
            $variant,
            (string) $data['quantity'],
            (string) $data['unit_cost'],
            $data['reference'] ?? null,
            $data['supplier_ref'] ?? null,
            $expiry,
        );

        return $this->json($this->serializeLot($lot), Response::HTTP_CREATED);
    }

    #[Route('/variants/{id}/lots', name: 'api_lots_list', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function listLots(string $id): JsonResponse
    {
        $variant = $this->getVariant($id);
        $lots = $this->stockLotRepository->findByVariantOrderedByReceivedAt($variant);

        return $this->json(array_map([$this, 'serializeLot'], $lots));
    }

    #[Route('/variants/{id}/stock', name: 'api_stock_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function showStock(string $id): JsonResponse
    {
        $variant = $this->getVariant($id);

        return $this->json([
            'variant_id' => (string) $variant->getId(),
            'available' => $this->stockMovementService->getAvailableStock($variant),
        ]);
    }

    #[Route('/variants/{id}/stock-out', name: 'api_stock_out', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function stockOut(string $id, Request $request): JsonResponse
    {
        $variant = $this->getVariant($id);
        $data = $request->toArray();
        if (empty($data['quantity'])) {
            return $this->json(['error' => 'quantity is required'], Response::HTTP_BAD_REQUEST);
        }

        $type = !empty($data['type']) ? MovementType::from($data['type']) : MovementType::Adjustment;

        try {
            $movement = $this->stockMovementService->stockOut(
                $variant,
                (string) $data['quantity'],
                $type,
                $data['reason'] ?? null,
                $data['allocations'] ?? null,
            );
        } catch (InsufficientStockException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->serializeMovement($movement), Response::HTTP_CREATED);
    }

    #[Route('/variants/{id}/adjustments', name: 'api_adjustments', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function adjust(string $id, Request $request): JsonResponse
    {
        $variant = $this->getVariant($id);
        $data = $request->toArray();
        if (empty($data['quantity']) || empty($data['direction'])) {
            return $this->json(['error' => 'quantity and direction are required'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($data['lot_id'])) {
            return $this->json(['error' => 'lot_id is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $movement = $this->stockMovementService->adjust(
                $variant,
                (string) $data['quantity'],
                MovementDirection::from($data['direction']),
                (string) $data['lot_id'],
                $data['reason'] ?? null,
            );
        } catch (InsufficientStockException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->serializeMovement($movement), Response::HTTP_CREATED);
    }

    #[Route('/stock-movements', name: 'api_stock_movements', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function listMovements(Request $request): JsonResponse
    {
        $variantId = $request->query->get('variant_id');
        $movements = $this->listStockMovementsHandler->handle(
            new ListStockMovementsQuery(
                variantId: \is_string($variantId) ? $variantId : null,
            ),
        );

        return $this->json($movements);
    }

    #[Route('/stock-alerts', name: 'api_stock_alerts', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function stockAlerts(): JsonResponse
    {
        $alerts = $this->stockAlertsHandler->handle(new GetStockAlertsQuery());

        return $this->json($alerts);
    }

    private function getVariant(string $id): \App\Catalog\Domain\Entity\ProductVariant
    {
        $variant = $this->variantRepository->findById(Uuid::fromString($id));
        if (null === $variant) {
            throw $this->createNotFoundException();
        }

        return $variant;
    }

    /** @return array<string, mixed> */
    private function serializeLot(\App\Inventory\Domain\Entity\StockLot $lot): array
    {
        return [
            'id' => (string) $lot->getId(),
            'variant_id' => (string) $lot->getVariant()->getId(),
            'reference' => $lot->getReference() ?? $lot->getVariant()->getSku(),
            'quantity_initial' => $lot->getQuantityInitial(),
            'quantity_remaining' => $lot->getQuantityRemaining(),
            'unit_cost' => $lot->getUnitCost(),
            'received_at' => $lot->getReceivedAt()->format(\DateTimeInterface::ATOM),
            'expiry_date' => $lot->getExpiryDate()?->format('Y-m-d'),
            'supplier_ref' => $lot->getSupplierRef(),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeMovement(\App\Inventory\Domain\Entity\StockMovement $movement): array
    {
        return [
            'id' => (string) $movement->getId(),
            'variant_id' => (string) $movement->getVariant()->getId(),
            'type' => $movement->getType()->value,
            'direction' => $movement->getDirection()->value,
            'quantity' => $movement->getQuantity(),
            'occurred_at' => $movement->getOccurredAt()->format(\DateTimeInterface::ATOM),
            'allocations' => array_map(static fn ($a) => [
                'lot_id' => (string) $a->getLot()->getId(),
                'quantity' => $a->getQuantity(),
            ], $movement->getAllocations()->toArray()),
        ];
    }
}
