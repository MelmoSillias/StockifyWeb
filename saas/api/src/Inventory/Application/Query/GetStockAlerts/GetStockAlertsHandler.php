<?php

namespace App\Inventory\Application\Query\GetStockAlerts;

use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use App\Inventory\Application\Service\StockMovementService;

final class GetStockAlertsHandler
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly StockMovementService $stockMovementService,
    ) {
    }

    /** @return list<array{variant_id: string, sku: string, available: string, alert_threshold: string|null}> */
    public function handle(GetStockAlertsQuery $query): array
    {
        $variants = $this->variantRepository->findWithAlertThresholdByShop($query->shopId);
        $alerts = [];

        foreach ($variants as $variant) {
            $available = $this->stockMovementService->getAvailableStock($variant);
            if (bccomp($available, (string) $variant->getAlertThreshold(), 3) < 0) {
                $alerts[] = [
                    'variant_id' => (string) $variant->getId(),
                    'sku' => $variant->getSku(),
                    'available' => $available,
                    'alert_threshold' => $variant->getAlertThreshold(),
                ];
            }
        }

        return $alerts;
    }
}
